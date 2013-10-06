
$(function () {
  $('textarea.codemirror.xml').each(function () {
    CodeMirror.fromTextArea(this, {
      mode: { name: "xml", alignCDATA: true },
      lineNumbers: true
    });
  });
});

$(document).on('click', 'a[href="#data-source-example-pane"]', function () {
  var panel = $('#data-source-example-pane');

  panel.empty().append('<i class="icon-refresh icon-spin icon-large"></i>');

  $.when(
    $.ajax({
      url: '/ajax.php',
      data: {
        function_name: 'get_first_lines_of_file',
        folder: 'building-permits',
        file: $('#data-source').val()
      },
      dataType: 'html'
    }),
    $.ajax({
      url: '/ajax.php',
      data: {
        function_name: 'get_first_lines_of_csv',
        folder: 'building-permits',
        file: $('#data-source').val(),
        delimiter: $('#delimiter').val()
      },
      dataType: 'json'
    })
  )
  .then(function (data1, data2) {
    panel.empty();

    var text = data1[0];

    panel.append($('<p>').append('First lines of file (raw text):'));
    panel.append($('<pre>').append(text));

    var csv = data2[0];

    panel.append($('<p>').append('First lines of file (HTML table):'));
    panel.append(
      $('<div>')
      .addClass('horizontal-overflow')
      .append(
        $('<table>')
        .addClass('table table-bordered')
        .append(
          $('<thead>')
          .append(
            $('<tr>').append($.map(csv[0], function (cell) {
              return $('<th>').text(cell);
            }))
          )
        )
        .append(
          $('<tbody>')
          .append(
            $('<tr>').append($.map(csv[1], function (cell, i) {
              return $('<td>').text(cell).css('fontStyle', cell == 'NULL' ? 'italic' : '').attr('title', '$col[' + i + ']');
            }))
          )
          .append(
            $('<tr>').append($.map(csv[2], function (cell, i) {
              return $('<td>').text(cell).css('fontStyle', cell == 'NULL' ? 'italic' : '').attr('title', '$col[' + i + ']');
            }))
          )
        )
      )
    );
  });
});

$(document).on('click', 'a[href="#uri-template-example-pane"]', function () {
  var panel = $('#uri-template-example-pane');

  panel.empty().append('<i class="icon-refresh icon-spin icon-large"></i>');

  $.when(
    $.ajax({
      url: '/ajax.php',
      data: {
        function_name: 'process_template_with_first_line_from_csv',
        folder: 'building-permits',
        file: $('#data-source').val(),
        delimiter: $('#delimiter').val(),
        variables: {
          template: $('#uri-template').val()
        }
      },
      dataType: 'html'
    })
  )
  .then(function (text) {
    panel.empty();
    panel.append($('<pre>').text(text));
  });
});

$(document).on('click', 'a[href="#rdf-template-example-pane"]', function () {
  var panel = $('#rdf-template-example-pane');

  panel.empty().append('<i class="icon-refresh icon-spin icon-large"></i>');

  $.when(
    $.ajax({
      url: '/ajax.php',
      data: {
        function_name: 'process_template_with_first_line_from_csv',
        folder: 'building-permits',
        file: $('#data-source').val(),
        delimiter: $('#delimiter').val(),
        variables: {
          template: $('#uri-template').val()
        }
      },
      dataType: 'html'
    })
  )
  .then(function (uri) {
    $.when(
      $.ajax({
        url: '/ajax.php',
        data: {
          function_name: 'process_template_with_first_line_from_csv',
          folder: 'building-permits',
          file: $('#data-source').val(),
          delimiter: $('#delimiter').val(),
          variables: {
            uri: uri,
            template: $('#rdf-template').val()
          }
        },
        dataType: 'html'
      })
    )
    .then(function (text) {
      panel.empty();
      panel.append($('<pre>').text(text));
    });
  });
});

$(document).on('click', '#generate-rdf', function () {
  var button = $(this);

  button.attr('disabled', true).data('original-content', button.html()).html('<i class="icon-cogs"></i> Generating …');
  
  var messages = $('#messages');
  
  messages.empty();

  var label = $('#generate-rdf-label');

  function generateRdfBatch(rowNumber) {
    $.when(
      $.ajax({
        url: '/ajax.php',
        data: {
          function_name: 'generate_rdf_batch',
          folder: 'building-permits',
          file: $('#data-source').val(),
          delimiter: $('#delimiter').val(),
          variables: {
            rdf_root_element: $('#rdf-root-element').val(),
            uri_template: $('#uri-template').val(),
            rdf_template: $('#rdf-template').val()
          },
          rowNumber: rowNumber
        },
        dataType: 'json'
      })
    )
    .then(function (response) {
      if (response.warnings.length > 0) {
        $.each(response.warnings, function (i, warning) {
          messages.append('<div class="alert alert-warning">' + '<strong>' + warning.message + '</strong>' + ' ' + warning.file + '</div>');
        });
      }

      if (response.lines_read > 0) {
        label.text(rowNumber + response.lines_read);
        setTimeout(function () { generateRdfBatch(rowNumber + response.lines_read); }, 1);
      } else {
        button.removeAttr('disabled').html(button.data('original-content'));
        label.html(rowNumber + ' rdf descriptions generated.' + ' <a href="/' + response.filename + '" target="blank"><i class="icon-download"></i> Download</a>');
      }
    });
  }

  generateRdfBatch(0);
});