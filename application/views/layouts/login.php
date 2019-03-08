<html lang="en-us" class="">
	<head>
		<?php if($header) echo $header ;?>
        
        <?php
            if (isset($css_files)) {
                foreach ($css_files as $css_file) {
                    echo '<link rel="stylesheet" href="' . $css_file . '">';
                }
            }
        ?>

    </head>
    
<body>
    <?php if($main) echo $main ;?>
    
    <?php if($footer) echo $footer ;?>
    
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