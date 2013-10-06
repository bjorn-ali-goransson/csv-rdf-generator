<?php
  
ini_set('display_errors', 1);

$function_name = 'ajax_' . $_REQUEST['function_name'];

if(function_exists($function_name)){
  return $function_name();
} else {
  return header('HTTP/1.1 500 No such function');
}



function ajax_get_first_lines_of_file(){
  $handle = fopen('data/' . $_REQUEST['file'], "r");
  echo fgets($handle);
  echo fgets($handle);
  echo fgets($handle);
  fclose($handle);
}



function ajax_get_first_lines_of_csv(){
  $delimiter = $_REQUEST['delimiter'];

  switch($delimiter){
    case 'tab': $delimiter = "\t"; break;
    case 'comma': $delimiter = ","; break;
    case 'semicolon': $delimiter = ";"; break;
    case 'pipe': $delimiter = "|"; break;
  }

  $handle = fopen('data/' . $_REQUEST['file'], "r");
  
  $lines = array();

  for($i = 0; $i < 3; $i++){
    $line = str_getcsv(fgets($handle), $delimiter);

    $lines[] = $line;
  }

  fclose($handle);

  echo json_encode($lines);
}



function ajax_process_template_with_first_line_from_csv(){
  if(isset($_REQUEST['variables'])){
    $variables = $_REQUEST['variables'];
  } else {
    $variables = array();
  }

  switch($_REQUEST['delimiter']){
    case 'tab': $delimiter = "\t"; break;
    case 'comma': $delimiter = ","; break;
    case 'semicolon': $delimiter = ";"; break;
    case 'pipe': $delimiter = "|"; break;
  }

  //

  $in_handle = fopen('data/' . $_REQUEST['file'], 'r');
  fgets($in_handle); // skip header row
  
  $variables['col'] = str_getcsv(fgets($in_handle), $delimiter);

  echo compile_and_render_template($variables['template'], $variables);

  fclose($in_handle);
}



function ajax_generate_rdf_batch(){
  $GLOBALS['RDF_ERRORS'] = array();

  set_error_handler(function($num, $str, $file, $line, $context = null){
    $GLOBALS['RDF_ERRORS'][] = (object) array('message' => $str, 'number' => $num, 'file' => $file, 'line' => $line);
  });

  if(isset($_REQUEST['variables'])){
    $variables = $_REQUEST['variables'];
  } else {
    $variables = array();
  }

  if(isset($_REQUEST['rowNumber'])){
    $skip = $_REQUEST['rowNumber'];
  } else {
    $skip = 0;
  }

  switch($_REQUEST['delimiter']){
    case 'tab': $delimiter = "\t"; break;
    case 'comma': $delimiter = ","; break;
    case 'semicolon': $delimiter = ";"; break;
    case 'pipe': $delimiter = "|"; break;
  }

  //

  $out_filename = 'output/' . date('Y-m-d') . '.xml';
  
  $out_handle = fopen($out_filename, $skip == 0 ? 'w' : 'a');
  
  if($skip == 0){
    fwrite($out_handle, '<?xml version="1.0"?>');
    fwrite($out_handle, "\n");
  
    fwrite($out_handle, $variables['rdf_root_element']);
    fwrite($out_handle, "\n");
  }

  $in_handle = fopen('data/' . $_REQUEST['file'], 'r');
  fgets($in_handle); // skip header row

  for($i = 0; $i < $skip; $i++){
    fgets($in_handle); // skip row
  }
  
  $rdf_template = compile_template($variables['rdf_template']);
  $uri_template = compile_template($variables['uri_template']);

  //

  for($i = 0; $i < 100; $i++){
    $line = fgets($in_handle);

    if($line === FALSE){
      break;
    }

    $variables['col'] = str_getcsv($line, $delimiter);
    $variables['uri'] = render_template($uri_template, $variables);

    fwrite($out_handle, render_template($rdf_template, $variables));
    fwrite($out_handle, "\n\n");
  }

  $response = (object) array('success' => TRUE, 'filename' => $out_filename, 'warnings' => $GLOBALS['RDF_ERRORS'], 'lines_read' => $i);
  
  echo json_encode($response);

  //
  
  fclose($in_handle);
  fclose($out_handle);

  set_error_handler(NULL);
}

function compile_and_render_template($template, $variables){
  return render_template(compile_template($template), $variables);
}

function compile_template($template){
  preg_match_all('@{[^}]+}@', $template, $matches);

  $compiled_template = new stdClass;

  $compiled_template->template = $template;
  $compiled_template->searches = $matches[0];
  $compiled_template->replacements = array();

  foreach($compiled_template->searches as $search){
    $compiled_template->replacements[] = 'return ' . substr($search, 1, -1) . ';';
  }

  return $compiled_template;
}

function render_template($template, $variables){
  extract($variables, EXTR_SKIP);

  $replacements = array();

  for($i = 0; $i < count($template->searches); $i++){
    $search = $template->searches[$i];
    $replacement = $template->replacements[$i];
    if(!isset($replacements[$search])){
      $replacements[$search] = eval($replacement);
    }
  }
  
  return str_replace($template->searches, $replacements, $template->template);
}