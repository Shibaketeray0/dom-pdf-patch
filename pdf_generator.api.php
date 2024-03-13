<?php

/**
 * @file
 * Hooks and documentation related to pdf_generator module.
 */

use Dompdf\Dompdf;

/**
 * Alter Dompdf object before rendering.
 *
 * @param \Dompdf\Dompdf $dompdf
 *   Dompdf instance.
 */
function hook_mymodule_pdf_generator_pre_render_alter(Dompdf &$dompdf) {
  // Allows other modules to alter the Dompdf instance before it is rendered.
  $dom = $dompdf->getDom();
  $html = $dom->saveHtml();
  $html = str_replace("Hello World!", "New World!", $html);
  $dompdf->loadHtml($html);
}

/**
 * Alter Dompdf object after rendering.
 *
 * @param \Dompdf\Dompdf $dompdf
 *   Dompdf instance.
 */
function hook_mymodule_pdf_generator_post_render_alter(Dompdf &$dompdf) {
  // Allows other modules to alter the Dompdf instance after it is rendered.
  $canvas = $dompdf->getCanvas();
  $canvas->page_text(200, 200, "New World!", NULL, 24);
  $dompdf->setCanvas($canvas);
}
