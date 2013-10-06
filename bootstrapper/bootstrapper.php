<?php
  
  // check number of submodules matching
  if(file_exists(dirname(__FILE__) . '/../lib/github-submodule-updater/github-submodule-updater.php') && file_exists(dirname(__FILE__) . '/../lib/github-submodule-updater/lib/gitmodules-parser/gitmodules-parser.php')){
    require dirname(__FILE__) . '/../lib/github-submodule-updater/github-submodule-updater.php';
  } else {
    require dirname(__FILE__) . '/github-submodule-updater.php';
  }

  if(isset($_GET['download-all-submodules'])){
    foreach(gitmodules_get_all() as $submodule){
      if(!$submodule->path_exists){
        github_submodule_updater_update($submodule);
      }
    }

    header('Location: /');
    die;
  }
    
  $i = 0;

  foreach(gitmodules_get_all() as $submodule){
    if(!$submodule->path_exists){
      $i++;
    }
  }

  if($i > 0){
    ?>
      <form>
        <p>Need to download <?php echo $i; ?> dependencies.</p>
        <input type="hidden" name="download-all-submodules" value="true" />
        <input type="submit" value="Ok" />
      </form>
    <?php
    die;
  }

?>