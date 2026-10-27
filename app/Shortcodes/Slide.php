<?php

namespace App\Shortcodes;

class Slide
{
    public function register($shortcode, $content, $compiler, $name, $viewData)
    {
        ob_start();
        ?>
        <li class="splide__slide">
            <a href="<?php echo $shortcode->link ?>">
                <img src="<?php echo $shortcode->image ?>" alt="">
            </a>
        </li>
        <?php
        return ob_get_clean();
    }
}
