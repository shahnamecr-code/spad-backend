<?php

namespace App\Shortcodes;
use Shortcode;

class Slider
{
    public function register($shortcode, $content, $compiler, $name, $viewData){
        ob_start();
        ?>
        <div class="splide slide-n1">
            <div class="splide__track">
                <ul class="splide__list">
                    <?php echo $content?>
                </ul>
            </div>
        </div>
<?php
        return ob_get_clean();
    }
}
