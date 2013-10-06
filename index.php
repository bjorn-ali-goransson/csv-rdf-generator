<?php
  ini_set('display_errors', 0);

  require dirname(__FILE__) . '/bootstrapper/bootstrapper.php';
  
  if(isset($_GET['update-submodule']) && isset($_GET['gitmodules'])){
    $submodule = gitmodules_get_by_name($_GET['update-submodule'], $_GET['gitmodules']);
    
    github_submodule_updater_update($submodule);

    header('Location: /');
    die;
  }

  function list_submodules($gitmodules_path = '', $level = 0){
    ?>
      <ul class="<?php if($level == 0) echo 'dropdown-menu'; ?>">
        <?php
          foreach(gitmodules_get_all($gitmodules_path) as $submodule){
            ?>
              <li>
                <a href="/?update-submodule=<?php echo rawurlencode($submodule->name); ?>&gitmodules=<?php echo rawurlencode($submodule->parent_path); ?>"><?php echo $submodule->repo; ?></a>
                <?php
                  if($submodule->gitmodules_exists){
                    list_submodules($submodule->path, $level + 1);
                  }
                ?>
              </li>
            <?php
          }
        ?>
      </ul>
    <?php
  }

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YEAH! RDF generator</title>

    <?php
      require dirname(__FILE__) . '/lib/lessphp/lessc.inc.php';

      if(!file_exists(dirname(__FILE__) . '/less/compiled')){
        mkdir(dirname(__FILE__) . '/less/compiled');
      }

      $less = new lessc;
      
      $less->compileFile(dirname(__FILE__) . '/less/bootstrap.less', dirname(__FILE__) . '/less/compiled/style.css');
    ?>
    <link rel="stylesheet" href="./less/compiled/style.css" />

    <link rel="stylesheet" href="./lib/codemirror/lib/codemirror.css">
    <script src="./lib/codemirror/lib/codemirror.js"></script>
    <script src="./lib/codemirror/mode/xml/xml.js"></script>
  </head>
  <body>
    <div class="container">
        <?php /*
      <nav class="navbar navbar-default" role="navigation">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="/" title="You! Enhance Access to History">YEAH!</a>
        </div>
        
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">Update dependencies <b class="caret"></b></a>
              <?php list_submodules(); ?>
            </li>
          </ul>
        </div><!-- /.navbar-collapse -->
      </nav>
        */ ?>

      <h1>RDF generator</h1>
      <div class="panel panel-default">
        <div class="panel-heading"><h2 class="panel-title">
          <i class="icon-download-alt"></i>
          Generate RDF:s from CSV
        </h2></div>
        <div class="panel-body">
          <ul class="nav nav-tabs">
            <li class="active"><a href="#data-source-pane" data-toggle="tab"><i class="icon-pencil"></i> Data source</a></li>
            <li><a href="#data-source-example-pane" data-toggle="tab" title="Try to generate output with these settings"><i class="icon-beaker"></i> Try</a></li>
          </ul>
          <form role="form" class="form-horizontal">

            <div class="tab-content">
              <div class="tab-pane fade in active" id="data-source-pane">
                <div class="form-group">
                  <label for="data-source" class="col-lg-2 control-label">Choose data source</label>
                  <div class="col-lg-10">
                    <select id="data-source" class="form-control monospace">
                      <?php 
                        $path = 'data/';
                        if ($handle = opendir($path)) {
                          while (false !== ($entry = readdir($handle))) {
                            if(is_file($path . $entry)){
                              echo "<option>$entry</option>";
                            }
                          }
                          closedir($handle);
                        }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="delimiter" class="col-lg-2 control-label">Delimiter</label>
                  <div class="col-lg-10">
                    <select id="delimiter" class="form-control monospace">
                      <option value="tab">Tab (&nbsp;&nbsp;&nbsp;&nbsp;)</option>
                      <option value="comma">Comma (,)</option>
                      <option value="semicolon">Semicolon (;)</option>
                      <option value="pipe">Pipe (|)</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="data-source-example-pane"></div>
            </div>
            
            <ul class="nav nav-tabs">
              <li class="active"><a href="#rdf-root-element-pane" data-toggle="tab"><i class="icon-pencil"></i> RDF root element</a></li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane fade in active" id="rdf-root-element-pane">
                <div class="form-group">
                  <label for="rdf-root-element" class="sr-only">RDF root element</label>
                  <div class="col-lg-12">
<textarea id="rdf-root-element" class="form-control monospace" rows="5">&lt;rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc= "http://purl.org/dc/elements/1.1/"
&gt;</textarea>
                  </div>
                </div>
              </div>
            </div>
            
            <ul class="nav nav-tabs">
              <li class="active"><a href="#uri-template-pane" data-toggle="tab"><i class="icon-pencil"></i> URI Template</a></li>
              <li><a href="#uri-template-example-pane" data-toggle="tab" title="Try to generate output with these settings"><i class="icon-beaker"></i> Try</a></li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane fade in active" id="uri-template-pane">
                <div class="form-group">
                  <label for="uri-template" class="sr-only">URI template</label>
                  <div class="col-lg-12">
                    <input id="uri-template" type="text" class="form-control monospace" value="http://<?php echo $_SERVER['SERVER_NAME']; ?>/{urlencode($col[0])}" />
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="uri-template-example-pane"></div>
            </div>

            <ul class="nav nav-tabs">
              <li class="active"><a href="#rdf-template-pane" data-toggle="tab"><i class="icon-pencil"></i> RDF Template</a></li>
              <li><a href="#rdf-template-example-pane" data-toggle="tab" title="Try to generate output with these settings"><i class="icon-beaker"></i> Try</a></li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane fade in active" id="rdf-template-pane">
                <div class="form-group">
                  <label for="rdf-template" class="sr-only">RDF template</label>
                  <div class="col-lg-12">
<textarea id="rdf-template" class="form-control monospace" rows="10">&lt;rdf:Description rdf:about="{$uri}"&gt;
  &lt;dc:title&gt;{$col[3]}, {$col[4]}&lt;/dc:title&gt;
&lt;/rdf:Description&gt;</textarea>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="rdf-template-example-pane"></div>
            </div>
          </form>

          <p><button class="btn btn-primary" id="generate-rdf"><i class="icon-cogs"></i> Generate RDF</button> <span id="generate-rdf-label"></span></p>

          <div id="messages"></div>
        </div>
        <div class="panel-footer"></div>
      </div>
    </div>
    
    <script src="/scripts/jquery-1.10.2.min.js"></script>
    <script src="/lib/bootstrap/dist/js/bootstrap.min.js"></script>
    <script src="/scripts/main.js"></script>
  </body>
</html>