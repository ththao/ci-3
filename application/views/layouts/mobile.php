<!DOCTYPE html>
<html lang="en">
	<head>
        <?= isset($header) ? $header : '' ?>
        
        <?php
            if (isset($css_files)) {
                foreach ($css_files as $css_file) {
                    echo '<link rel="stylesheet" href="' . $css_file . '">';
                }
            }
        ?>
    </head>
    
<body id="mobile-workboard">
    <?= isset($banner) ? $banner : '' ?>
    
    <?= isset($main) ? $main : '' ?>
    
    <?= isset($footer) ? $footer : '' ?>
    
    <?php
        if (isset($js_files)) {
            foreach ($js_files as $js_file) {
                echo '<script src="' . $js_file . '"></script>';
            }
        }
    ?>
    
    <script>
        <?= isset($js_script) ? $js_script : '';?>
    </script>
</body>
</html>