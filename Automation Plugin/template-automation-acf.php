<?php
/**
 * Template Name: Automation Page
 * Description: For the automation pages
 */

if (!defined('ABSPATH')) exit;

ar_template_define_constants();

// figure out which page is being viewed
$pid = ar_template_resolve_post_id();

// pull ACF fields + post meta into one array for quick lookups
$AR_CTX = ar_template_collect_context($pid);

// register small helper functions like af(), af_html(), etc
ar_template_register_field_helpers();

// used to avoid breaking Gravity Forms AJAX/submission requests
$is_gf_request = ar_template_is_gravity_forms_request();

// strips the WP admin bar markup if it sneaks into output
ar_template_ensure_strip_wp_adminbar();

// phone is displayed and also used for tel: links
$display_phone = af('phone_number', '');
$tel_href      = af_make_tel($display_phone);

// gather hosts used by embedded forms (CSP allowlist)
$form_hosts = ar_template_collect_form_hosts();

// optional CSP header (controlled by AR_ENABLE_CSP)
ar_template_register_csp($form_hosts);

// capture the inner markup so it can be wrapped in a clean WP skeleton later
ob_start();
?>
<!-- Body Inner Markup Start (no <html>/<head>/<body>) -->
  <div class="custom-page">
    <!-- Fixed Site Header (replaces old navbar + header-div) -->
    <header class="site-header" id="site-header">
      <div class="header-inner">
        <a href="#Home" class="brand">
          <img class="brand-logo" src="<?php echo esc_url(af('logo')); ?>" alt="Business Logo" loading="lazy" />
        </a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation">
          <span class="bar"></span>
          <span class="bar"></span>
          <span class="bar"></span>
        </button>
        <nav class="primary-nav" id="primary-nav" aria-label="Main navigation">
          <ul class="nav-list">
            <li class="nav-item"><a href="#Home">Home</a></li>
            <li class="nav-item"><a href="#Section-2">About Us</a></li>
            <li class="nav-item"><a href="#Service-Areas">Service Areas</a></li>
            <li class="nav-item"><a href="#Testimonials">Testimonials</a></li>
            <li class="nav-item"><a href="#Products">Products</a></li>
            <li class="nav-item nav-cta">
              <a class="nav-quote-btn" role="button" tabindex="0" data-quote-trigger>Get Quote</a>
            </li>
          </ul>
        </nav>
      </div>
    </header>

    <!--Start of Home code-->
    <div class="home-div">
      <div class="home-new" id="Home">
        <div class="home-new-content">
          <div class="home-new-content-1">
            <img class="home-new-image" src="<?php echo esc_url(af('section_2_img')); ?>" alt="<?php echo esc_attr(af('section_2_img_alt')); ?>" loading="lazy" referrerpolicy="no-referrer">
          </div>
        </div>
        <div class="home-new-content-2">
          <h1 class="home-new-heading"><?php echo af_html('heading_home'); ?></h1>
          <h3 class="h3-home-new">Call today for a free quote
            <a class="home-phone-new" href="tel:<?php echo esc_attr($tel_href); ?>"> <?php echo esc_html($display_phone); ?></a>
          </h3>
          <h2 class="h2-2-home-new"><?php echo af_html('h2_home'); ?></h2>
          <h2 class="h2-2-home-new"><strong>Fast, Easy, &amp; 100% Free To Get Started</strong></h2>
          <div class="iframe-simple-wrapper-2">
            <?php // Hero form shortcode (unique)
            echo ar_gf_shortcode_or_safe('form'); ?>
          </div>
        </div>
      </div>
    </div>
    <!--End of Home code-->

    <!-- Begin Post-Hero Background Wrapper -->
    <div class="post-hero-bg">

      <!--Start of Section 3-->
      <section id="Section-3">
        <div class="section-3-column">
          <div class="button-title-row">
            <h3 class="cards-wheel__title">Why Customers Choose Us</h3>
            <button class="button-4" data-quote-trigger>Get Quote</button>
          </div>
          <div class="section-3-row">
            <div class="section-3-item1">
              <h3 class="h2-section-3">
                <?php echo af_html('h3_section_3_1'); ?>
              </h3>
              <p class="p-section-3">
                <?php echo af_html('p_section_3_1'); ?>
              </p>
            </div>
            <div class="section-3-item2">
              <h3 class="h2-section-3">
                <?php echo af_html('h3_section_3_2'); ?>
              </h3>
              <p class="p-section-3">
                <?php echo af_html('p_section_3_2'); ?>
              </p>
            </div>
            <div class="section-3-item3">
              <h3 class="h2-section-3">
                <?php echo af_html('h3_section_3_3'); ?>
              </h3>
              <p class="p-section-3">
                <?php echo af_html('p_section_3_3'); ?>
              </p>
            </div>
          </div>
        </div>
      </section>
      <!--End of Section 3-->

      <!--Start of Section 2-->
      <section id="Section-2">
        <div class="section-2-wrapper">
          <div class="section-2">
            <div class="section-2-item-1">
              <h2 class="h2-section-2">
                <?php echo af_html('h2_section_2'); ?>
              </h2>

              <div class="background-wrapper-2">
                <div class="phone-text-wrapper">
                  <h3 class="h3-section-2">
                    Call for a Free Quote Today
                  </h3>
                  <h3 class="phone2">
                    <a class="phone2" href="tel:<?php echo esc_attr($tel_href); ?>"> <?php echo esc_html($display_phone); ?>
                    </a>
                  </h3>
                </div>

                <p class="p-section-2">
                  <?php echo af_html('p_section_2'); ?>
                </p>

                <button class="button-4" data-quote-trigger>Get Quote</button>
              </div>
            </div>
            <div class="section-2-item-2">
              <img class="section-2-img" src="<?php echo esc_url(af('section_4_img')); ?>" alt="<?php echo esc_attr(af('section_4_img_alt')); ?>" loading="lazy" referrerpolicy="no-referrer">
            </div>
          </div>
        </div>
      </section>
      <!--End of Section 2-->

      <!-- Cards Wheel Section with Placeholders -->
      <div class="cards-wheel-bg">
        <section class="cards-wheel">
          <div class="button-title-row">
            <h2 class="cards-wheel__title">Our Products</h2>
            <button class="button-4" data-quote-trigger>Get Quote</button>
          </div>
          <div id="Products" class="cards-wheel__container" tabindex="0">
            <!-- Card 1 -->
            <div class="cards-wheel__card">
              <div class="cards-wheel__header"><?php echo af_html('card1_title'); ?></div>
              <div class="cards-wheel__image">
                <img src="<?php echo esc_url(af('card1_image_url')); ?>" alt="<?php echo esc_attr(af('card1_image_alt')); ?>" referrerpolicy="no-referrer">
              </div>
              <div class="cards-wheel__description">
                <p><?php echo af_html('card1_description'); ?></p>
              </div>
            </div>
            <!-- Card 2 -->
            <div class="cards-wheel__card">
              <div class="cards-wheel__header"><?php echo af_html('card2_title'); ?></div>
              <div class="cards-wheel__image">
                <img src="<?php echo esc_url(af('card2_image_url')); ?>" alt="<?php echo esc_attr(af('card2_image_alt')); ?>" referrerpolicy="no-referrer">
              </div>
              <div class="cards-wheel__description">
                <p><?php echo af_html('card2_description'); ?></p>
              </div>
            </div>
            <!-- Card 3 -->
            <div class="cards-wheel__card">
              <div class="cards-wheel__header"><?php echo af_html('card3_title'); ?></div>
              <div class="cards-wheel__image">
                <img src="<?php echo esc_url(af('card3_image_url')); ?>" alt="<?php echo esc_attr(af('card3_image_alt')); ?>" referrerpolicy="no-referrer">
              </div>
              <div class="cards-wheel__description">
                <p><?php echo af_html('card3_description'); ?></p>
              </div>
            </div>
          </div>
        </section>
      </div>
      <!--End of Card Wheel Section-->

      <!--Start of Service Areas (Refactored) -->
      <section id="Service-Areas" class="service-areas-section">
        <h2 class="service-areas-heading">We Proudly Serve</h2>
        <div class="service-areas-grid" id="service-areas-grid">
          <!-- First 16 visible -->
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_1_url')); ?>"><?php echo esc_html(af('service_area_1')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_2_url')); ?>"><?php echo esc_html(af('service_area_2')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_3_url')); ?>"><?php echo esc_html(af('service_area_3')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_4_url')); ?>"><?php echo esc_html(af('service_area_4')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_5_url')); ?>"><?php echo esc_html(af('service_area_5')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_6_url')); ?>"><?php echo esc_html(af('service_area_6')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_7_url')); ?>"><?php echo esc_html(af('service_area_7')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_8_url')); ?>"><?php echo esc_html(af('service_area_8')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_9_url')); ?>"><?php echo esc_html(af('service_area_9')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_10_url')); ?>"><?php echo esc_html(af('service_area_10')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_11_url')); ?>"><?php echo esc_html(af('service_area_11')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_12_url')); ?>"><?php echo esc_html(af('service_area_12')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_13_url')); ?>"><?php echo esc_html(af('service_area_13')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_14_url')); ?>"><?php echo esc_html(af('service_area_14')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_15_url')); ?>"><?php echo esc_html(af('service_area_15')); ?></a>
          <a class="service-area-tile" href="<?php echo esc_url(af('service_area_16_url')); ?>"><?php echo esc_html(af('service_area_16')); ?></a>

          <!-- Hidden (14 more) -->
          <div class="service-area-extra" hidden>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_17_url')); ?>"><?php echo esc_html(af('service_area_17')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_18_url')); ?>"><?php echo esc_html(af('service_area_18')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_19_url')); ?>"><?php echo esc_html(af('service_area_19')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_20_url')); ?>"><?php echo esc_html(af('service_area_20')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_21_url')); ?>"><?php echo esc_html(af('service_area_21')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_22_url')); ?>"><?php echo esc_html(af('service_area_22')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_23_url')); ?>"><?php echo esc_html(af('service_area_23')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_24_url')); ?>"><?php echo esc_html(af('service_area_24')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_25_url')); ?>"><?php echo esc_html(af('service_area_25')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_26_url')); ?>"><?php echo esc_html(af('service_area_26')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_27_url')); ?>"><?php echo esc_html(af('service_area_27')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_28_url')); ?>"><?php echo esc_html(af('service_area_28')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_29_url')); ?>"><?php echo esc_html(af('service_area_29')); ?></a>
            <a class="service-area-tile" href="<?php echo esc_url(af('service_area_30_url')); ?>"><?php echo esc_html(af('service_area_30')); ?></a>
          </div>
        </div>

        <button id="service-areas-toggle" class="button-4 service-areas-toggle" type="button" aria-expanded="false" aria-controls="service-areas-grid">View More</button>
      </section>
      <!--End of Service Areas (Refactored) -->

      <!-- services section -->
      <div class="services-bg">
        <section class="services-section">
          <div class="button-title-row-2">
            <h2 class="services-title">Our Services</h2>
            <button class="button-4" data-quote-trigger>Get Quote</button>
          </div>
          <div class="services-grid">
            <div class="service-box service-box--dark">
              <h3 class="service-box__title"><?php echo af_html('service_title_1'); ?></h3>
              <p class="service-box__desc"><?php echo af_html('service_description_1'); ?></p>
            </div>
            <div class="service-box service-box--light">
              <h3 class="service-box__title"><?php echo af_html('service_title_2'); ?></h3>
              <p class="service-box__desc"><?php echo af_html('service_description_2'); ?></p>
            </div>
            <div class="service-box service-box--dark">
              <h3 class="service-box__title"><?php echo af_html('service_title_3'); ?></h3>
              <p class="service-box__desc"><?php echo af_html('service_description_3'); ?></p>
            </div>
            <div class="service-box service-box--light">
              <h3 class="service-box__title"><?php echo af_html('service_title_4'); ?></h3>
              <p class="service-box__desc"><?php echo af_html('service_description_4'); ?></p>
            </div>
            <div class="service-box service-box--dark">
              <h3 class="service-box__title"><?php echo af_html('service_title_5'); ?></h3>
              <p class="service-box__desc"><?php echo af_html('service_description_5'); ?></p>
            </div>
            <div class="service-box service-box--light">
              <h3 class="service-box__title"><?php echo af_html('service_title_6'); ?></h3>
              <p class="service-box__desc"><?php echo af_html('service_description_6'); ?></p>
            </div>
            <div class="service-box service-box--dark">
              <h3 class="service-box__title"><?php echo af_html('service_title_7'); ?></h3>
              <p class="service-box__desc"><?php echo af_html('service_description_7'); ?></p>
            </div>
            <div class="service-box service-box--light">
              <h3 class="service-box__title"><?php echo af_html('service_title_8'); ?></h3>
              <p class="service-box__desc"><?php echo af_html('service_description_8'); ?></p>
            </div>
          </div>
        </section>
      </div>
      <!--End of Services Section-->

      <!--Start of Testimonials Section-->
      <div class="testimonials-wrapper">
        <div class="testimonial-section" id="Testimonials">
          <div class="testimonial-slide active">
            <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
            <p class="testimonial-text"><?php echo af_html('testimonial_text_1'); ?></p>
            <div class="testimonial-author"><?php echo esc_html(af('testimonial_name_1')); ?></div>
          </div>
          <div class="testimonial-slide">
            <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
            <p class="testimonial-text"><?php echo af_html('testimonial_text_2'); ?></p>
            <div class="testimonial-author"><?php echo esc_html(af('testimonial_name_2')); ?></div>
          </div>
          <div class="testimonial-slide">
            <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
            <p class="testimonial-text"><?php echo af_html('testimonial_text_3'); ?></p>
            <div class="testimonial-author"><?php echo esc_html(af('testimonial_name_3')); ?></div>
          </div>
          <button class="arrow arrow-left" type="button" aria-label="Previous testimonial">
            <svg class="arrow-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 5L8 12l6.5 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <button class="arrow arrow-right" type="button" aria-label="Next testimonial">
            <svg class="arrow-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.5 5L16 12l-6.5 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>
      <!--End of Testimonials Section-->

      <!--Start of Section 5-->
      <section id="Section-5">
        <div class="section-5">
          <div class="section-5-item-1">
            <h2 class="h2-section-5">
              <?php echo af_html('h2_section_5'); ?>
            </h2>
            <p class="p-section-5">
              <?php echo af_html('p_section_5_1'); ?>
            </p>
            <button class="button-4" data-quote-trigger>Get Quote</button>
          </div>
          <div class="section-5-item-2">
            <img class="section-5-img" src="<?php echo esc_url(af('section_5_img')); ?>" alt="<?php echo esc_attr(af('section_5_img_alt')); ?>" loading="lazy" referrerpolicy="no-referrer">
            <p class="p-section-5">
              <?php echo af_html('p_section_5_5'); ?>
            </p>
            <p class="p-section-5">
              <?php echo af_html('p_section_5_6'); ?>
            </p>
          </div>
        </div>
      </section>
      <!--End of Section 5-->

      <!--Start of Section 6-->
      <div class="section-6-align">
        <div class="section-6">
          <p class="p-section-6">
            <?php echo af_html('p_section_6'); ?>
          </p>
          <p class="p-section-6">
            <?php echo af_html('p_section_6_2'); ?>
          </p>
          <p class="p-section-6">
            <?php echo af_html('p_section_6_3'); ?>
          </p>
        </div>
      </div>
      <!--End of Section 6-->

      <!--Start of Section 7-->
      <section id="Section-7">
        <div class="section-7-align">
          <div class="section-7">
            <h2 class="h2-section-7"><?php echo esc_html(af('h2_section_7')); ?></h2>
            <div class="collapsible-container">
              <hr class="hr7">
              <button class="collapsible">
                <span class="icon">+</span>
                <span><?php echo esc_html(af('collapsible_title_1')); ?></span>
              </button>
              <div class="content">
                <p><?php echo af_html('collapsible_content_1'); ?></p>
              </div>
            </div>
            <div class="collapsible-container">
              <hr class="hr7">
              <button class="collapsible">
                <span class="icon">+</span>
                <span><?php echo esc_html(af('collapsible_title_2')); ?></span>
              </button>
              <div class="content">
                <p><?php echo af_html('collapsible_content_2'); ?></p>
              </div>
            </div>
            <div class="collapsible-container">
              <hr class="hr7">
              <button class="collapsible">
                <span class="icon">+</span>
                <span><?php echo esc_html(af('collapsible_title_3')); ?></span>
              </button>
              <div class="content">
                <p><?php echo af_html('collapsible_content_3'); ?></p>
              </div>
            </div>
            <div class="collapsible-container">
              <hr class="hr7">
              <button class="collapsible">
                <span class="icon">+</span>
                <span>Are We Able To Service All Types Of Events And Construction Services?</span>
              </button>
              <div class="content">
                <p><?php echo af_html('collapsible_content_4'); ?></p>
              </div>
            </div>
          </div>
          <hr class="hr7">
        </div>
      </section>
      <!--End of Section 7-->

      <!--Start of Partners Section-->
      <div class="partners-text-div">
        <h4 class="partners-h4">Partners</h4>
      </div>
      <div class="partners">
        <div class="service-areas-grid">
      <?php
      // Support either capitalized (Partner_1) or lowercase (partner_1) field names.
      for ($i = 1; $i <= 4; $i++) {
        $name = af('Partner_' . $i, '');
        $url  = af('Partner_' . $i . '_url', '');
        if ($name === '') {
          // Fallback to lowercase variant if capitalized empty
          $name = af('partner_' . $i, '');
        }
        if ($url === '') {
          $url = af('partner_' . $i . '_url', '');
        }
        if ($name === '' && $url === '') {
          continue; // nothing to show for this slot
        }
        // If still no URL but we have a name, render without href for safety
        $esc_name = esc_html($name);
        $esc_url  = esc_url($url);
        if ($esc_url) {
          echo '<a class="service-area-tile" href="' . $esc_url . '">' . $esc_name . '</a>';
        } else {
          echo '<span class="service-area-tile" role="text">' . $esc_name . '</span>';
        }
      }
      ?>
        </div>
      </div>
      <!--End of Partners Section-->

      <div class="bottom-quote-form">
        <div class="bottom-quote-form-inner">
          <h4 class="partners-h4">Get a Free Quote</h4>
          <div class="iframe-simple-wrapper-3">
            <?php // Bottom form shortcode (separate)
            echo ar_gf_shortcode_or_safe('form2'); ?>
          </div>
        </div>
      </div>

    </div><!-- /.post-hero-bg -->

    <!--Start of Footer-->
    <footer>
      <div class="footer">
        <div class="footer-item-1">
          <img class="logo" src="<?php echo esc_url(af('logo')); ?>" alt="" loading="lazy" referrerpolicy="no-referrer">
        </div>
        <div class="footer-item-2">
          <a target="_blank" rel="noopener noreferrer" class="phone2" href="tel:<?php echo esc_attr($tel_href); ?>">
            <?php echo esc_html($display_phone); ?>
          </a>
          <div class="logo-text-wrapper">
            <p class="powered-by"><strong>Powered by</strong></p>
            <a class="fusion-a" href="https://fusionsite.com/">
              <img
                class="fusion"
                src="https://fusionsiteservices.com/wp-content/uploads/2025/06/FSS-logo-main.png"
                alt="FusionSite wordmark logo in bold white text"
                loading="lazy"
                referrerpolicy="no-referrer"
              >
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div><!-- /.custom-page -->

  <!-- Quote Popup (hidden by default) -->
  <div class="quote-popup-overlay" id="quote-popup" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Get a Free Quote" data-ar-quote-popup>
  <div class="quote-popup" role="document" tabindex="-1" data-ar-quote-dialog>
      <button type="button" class="quote-popup-close" id="quote-popup-close" aria-label="Close quote form">✕</button>
      <h2 class="quote-popup-title">Get a Free Quote!</h2>
  <div class="quote-popup-iframe-wrapper">
  <?php // Popup form shortcode (third distinct)
  echo ar_gf_shortcode_or_safe('form3'); ?>
  </div>
    </div>
  </div>


<?php

// wraps the captured markup in a full document + wp_head/wp_footer
ar_template_finalize_output($pid, $is_gf_request, ob_get_clean());

function ar_template_define_constants(): void {
  if (!defined('AR_AUTOMATION_VERSION')) {
    // fallback version for cache-busting if the plugin constant isn't present
    define('AR_AUTOMATION_VERSION', '1.1.7');
  }
}

function ar_template_resolve_post_id(): int {
  // normal page view
  $queried = get_queried_object_id();
  if ($queried) {
    return (int) $queried;
  }

  // fallback for edge cases
  $fallback = get_the_ID();
  return $fallback ? (int) $fallback : 0;
}

function ar_template_collect_context(int $pid): array {
  if (!$pid) {
    return [];
  }

  // one place to read fields from (ACF + meta)
  $ctx = [];

  // ACF fields first (if ACF is installed)
  if (function_exists('get_fields')) {
    $all = get_fields($pid);
    if (is_array($all)) {
      $ctx = $all;
    }
  }

  // then overlay post meta (lets REST saves work even without ACF)
  $pm = get_post_meta($pid);
  if (is_array($pm)) {
    foreach ($pm as $key => $values) {
      if ($values === null) {
        continue;
      }
      $ctx[$key] = is_array($values) ? (string) reset($values) : (string) $values;
    }
  }

  return $ctx;
}

function ar_template_register_field_helpers(): void {
  if (!function_exists('af')) {
    function af($key, $default = '') {
      global $AR_CTX;
      // grab a value from the context array
      $val = null;
      if (isset($AR_CTX[$key]) && $AR_CTX[$key] !== '' && $AR_CTX[$key] !== null) {
        $val = $AR_CTX[$key];
      }
      if ($val === null || $val === '') {
        $val = $default;
      }
      // keep output predictable (string-ish only)
      return is_string($val) ? $val : (is_scalar($val) ? (string) $val : $default);
    }
  }

  if (!function_exists('af_make_tel')) {
    function af_make_tel($display_phone) {
      // strip formatting so tel: links work
      return preg_replace('/[^\d\+]/', '', (string) $display_phone);
    }
  }

  if (!function_exists('af_html')) {
    function af_html($key, $default = '') {
      $raw = af($key, $default);
      $allowed = wp_kses_allowed_html('post');
      // allow basic post HTML, strip the rest
      return wp_kses($raw, $allowed);
    }
  }

  if (!function_exists('ar_gf_shortcode_or_safe')) {
    function ar_gf_shortcode_or_safe(string $acf_key): string {
      // only allow real [gravityform ...] shortcodes
      $raw = trim((string) af($acf_key, ''));
      if ($raw === '') {
        return '';
      }
      if (preg_match('/^\[gravityform\b[^\]]*\]$/i', $raw)) {
        return $raw;
      }
      return '<!-- rejected-non-gf-shortcode -->' . esc_html($raw);
    }
  }
}

function ar_template_is_gravity_forms_request(): bool {
  // GF submissions/AJAX have their own POST payloads and query params
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['gform_submit']) || isset($_POST['gform_unique_id'])) {
      return true;
    }
    $submit_keys = array_filter(
      array_keys($_POST),
      static function ($key) {
        return preg_match('/^is_submit_\d+$/', (string) $key);
      }
    );
    if (!empty($submit_keys)) {
      return true;
    }
  }

  // common GF AJAX flags
  return isset($_REQUEST['gform_ajax'])
    || isset($_GET['gf_token'])
    || isset($_GET['gf_protect_submission'])
    || isset($_GET['gform_submit']);
}

function ar_template_ensure_strip_wp_adminbar(): void {
  if (function_exists('ar_strip_wp_adminbar')) {
    return;
  }

  function ar_strip_wp_adminbar($html) {
    // quick and dirty cleanup to keep the template output isolated
    if (!is_string($html) || $html === '') {
      return $html;
    }

    $patterns = [
      '/<div[^>]*id=["\']wpadminbar["\'][\s\S]*?<\/div>/i',
      '/<link[^>]*id=["\']admin-bar-css[^>]*>/i',
      '/<style[^>]*id=["\']wp-admin-bar["\'][^>]*>[\s\S]*?<\/style>/i',
    ];

    foreach ($patterns as $regex) {
      $html = preg_replace($regex, '', $html);
    }

    $html = preg_replace('/(<html[^>]*?)\sstyle="[^"]*margin-top\s*:\s*\d+px[^"]*"/i', '$1', $html);
    $html = preg_replace('/(<body[^>]*?)\sstyle="[^"]*margin-top\s*:\s*\d+px[^"]*"/i', '$1', $html);

    return $html;
  }
}


function ar_template_collect_form_hosts(): array {
  // these are stored as ACF fields (shortcodes or URLs)
  $form_urls = [
    af('form', ''),
    af('form2', ''),
    af('form3', ''),
  ];

  $hosts = [];

  foreach ($form_urls as $url) {
    if (!$url) {
      continue;
    }
    $scheme = parse_url($url, PHP_URL_SCHEME);
    $host   = parse_url($url, PHP_URL_HOST);
    if ($scheme && $host) {
      $base = $scheme . '://' . $host;
      if (!in_array($base, $hosts, true)) {
        $hosts[] = $base;
      }
    }
  }

  return $hosts;
}

function ar_template_register_csp(array $form_hosts): void {
  add_action(
    'send_headers',
    static function () use ($form_hosts) {
      // toggleable on/off
      if (!defined('AR_ENABLE_CSP') || !AR_ENABLE_CSP) {
        return;
      }
      if (!is_singular('page')) {
        return;
      }

      $post_id = get_queried_object_id();
      if (!$post_id || !ar_is_our_template($post_id)) {
        return;
      }

      $script_hosts = [
        'https://www.googletagmanager.com',
        'https://www.google-analytics.com',
        'https://554511.tctm.co',
      ];

      $connect_hosts = [
        'https://www.google-analytics.com',
      ];

      $frame_hosts = array_values($form_hosts);

      $script_hosts  = apply_filters('ar_csp_script_hosts', $script_hosts, $post_id);
      $connect_hosts = apply_filters('ar_csp_connect_hosts', $connect_hosts, $post_id);
      $frame_hosts   = apply_filters('ar_csp_frame_hosts', $frame_hosts, $post_id);

      $script_src  = "'self' 'unsafe-inline' " . implode(' ', array_unique($script_hosts));
      $style_src   = "'self' 'unsafe-inline' https://fonts.googleapis.com";
      $img_src     = "'self' https: data:";
      $font_src    = "'self' https://fonts.gstatic.com data:";
      $connect_src = "'self' " . implode(' ', array_unique($connect_hosts));
      $frame_src   = "'self'" . (empty($frame_hosts) ? '' : ' ' . implode(' ', array_unique($frame_hosts)));
      $child_src   = $frame_src;

      $csp = "default-src 'self'; "
        . "script-src {$script_src}; "
        . "style-src {$style_src}; "
        . "img-src {$img_src}; "
        . "font-src {$font_src}; "
        . "connect-src {$connect_src}; "
        . "frame-src {$frame_src}; "
        . "child-src {$child_src}; "
        . "object-src 'none'; "
        . "base-uri 'none'; "
        . "frame-ancestors 'self'; "
        . "upgrade-insecure-requests;";

      $csp = apply_filters(
        'ar_csp_policy',
        $csp,
        [
          'post_id'       => $post_id,
          'script_hosts'  => $script_hosts,
          'connect_hosts' => $connect_hosts,
          'frame_hosts'   => $frame_hosts,
        ]
      );

      // send CSP header before output starts
      if (!headers_sent()) {
        header("Content-Security-Policy: {$csp}");
      }
    },
    9
  );
}

function ar_template_finalize_output(int $pid, bool $is_gf_request, string $full_capture): void {
  // strip down to the inner markup (no outer html/body)
  $inner_html = ar_template_extract_inner_html($full_capture);

  global $AR_CTX;

  // optional renderer override (if a renderer function exists elsewhere)
  if (function_exists('ar_render_html')) {
    $rendered = ar_render_html(is_array($AR_CTX) ? $AR_CTX : []);
    if (!empty($rendered)) {
      $inner_html = $rendered;
    }
  }

  if (
    function_exists('ar_strip_wp_adminbar')
    && (stripos($inner_html, 'id="wpadminbar"') !== false || stripos($inner_html, 'admin-bar-css') !== false)
  ) {
    $inner_html = ar_strip_wp_adminbar($inner_html);
  }

  if (trim($inner_html) === '') {
    $inner_html = '<div class="ar-empty-template">' . esc_html__('No template content available.', 'automation-renderer') . '</div>';
  }

  // output a full document shell around the content
  ar_template_output_fresh_skeleton($inner_html, $pid);
}

function ar_template_extract_inner_html(string $capture): string {
  // if something injected a full <body>, only keep what's inside
  $inner = $capture;

  if (preg_match('/<body[^>]*>(.*)<\/body>/is', $capture, $matches)) {
    $inner = $matches[1];
  }

  return trim($inner);
}

function ar_template_output_fresh_skeleton(string $inner_html, int $pid): void {
  // this template prints the whole document so it can stay isolated
  echo "<!DOCTYPE html>\n<html ";
  language_attributes();
  echo ">\n<head>\n<meta charset='" . esc_attr(get_bloginfo('charset')) . "'>\n<meta name='viewport' content='width=device-width, initial-scale=1' />\n";
  wp_head();
  echo "</head>\n<body ";
  body_class('body');
  echo ">\n";
  if (function_exists('wp_body_open')) {
    wp_body_open();
  }

  // let shortcodes render (Gravity Forms lives here)
  echo do_shortcode($inner_html);

  if (current_user_can('edit_pages')) {
    // handy for sanity-checking whether the page is rendering fresh
    echo "\n<!-- Rendered fresh at: " . esc_html(current_time('mysql')) . " -->";
  }

  wp_footer();
  echo "\n</body></html>";
}


