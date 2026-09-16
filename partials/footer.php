<?php
/* Shared footer (new ITX design) + floating WhatsApp + scroll-top + script.
   Requires: $logo_url, $site_name, $footer_text, $socials, $nav_prefix,
   $ct_phone, $ct_email, $ct_email2, $ct_address, $wa_number, $wa_msg. */
?>
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-about">
        <div class="brand"><img src="<?= e($logo_url) ?>" alt="<?= e($site_name) ?>" loading="lazy" decoding="async"></div>
        <p data-ar="شركة تقنية رائدة تقدّم حلولاً برمجية وأنظمة أمان متكاملة بمعايير عالمية في السودان والمنطقة العربية."
           data-en="A leading technology company delivering integrated software solutions and security systems to world-class standards across Sudan and the Arab region.">شركة تقنية رائدة تقدّم حلولاً برمجية وأنظمة أمان متكاملة بمعايير عالمية في السودان والمنطقة العربية.</p>
        <div class="footer-social">
          <?php if ($socials): foreach ($socials as $s): ?>
          <a href="<?= e($s['url'] ?: '#') ?>" title="<?= e($s['platform']) ?>" target="_blank" rel="noopener">
            <i class="<?= e($s['icon']) ?>"></i>
          </a>
          <?php endforeach; else: ?>
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <?php endif; ?>
          <?php if ($wa_number): ?>
          <a href="https://wa.me/<?= e($wa_number) ?>" title="WhatsApp" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <div class="footer-col">
        <h4 data-ar="الشركة" data-en="Company">الشركة</h4>
        <a href="<?= $nav_prefix ?>#about"     data-ar="من نحن"   data-en="About">من نحن</a>
        <a href="<?= $nav_prefix ?>#services"  data-ar="خدماتنا"  data-en="Services">خدماتنا</a>
        <a href="<?= $nav_prefix ?>#our-works" data-ar="أعمالنا"  data-en="Work">أعمالنا</a>
        <a href="blog.php"                      data-ar="المدونة"  data-en="Blog">المدونة</a>
      </div>

      <div class="footer-col">
        <h4 data-ar="خدماتنا" data-en="Services">خدماتنا</h4>
        <a href="<?= $nav_prefix ?>#services" data-ar="تطوير المواقع"   data-en="Web development">تطوير المواقع</a>
        <a href="<?= $nav_prefix ?>#services" data-ar="تطبيقات الموبايل" data-en="Mobile apps">تطبيقات الموبايل</a>
        <a href="<?= $nav_prefix ?>#services" data-ar="كاميرات المراقبة" data-en="CCTV">كاميرات المراقبة</a>
        <a href="<?= $nav_prefix ?>#services" data-ar="أنظمة الأمان"     data-en="Security">أنظمة الأمان</a>
      </div>

      <div class="footer-col footer-contact">
        <h4 data-ar="تواصل" data-en="Contact">تواصل</h4>
        <?php if ($ct_phone): ?>
        <a href="tel:<?= e($ct_phone) ?>"><i class="fas fa-phone" aria-hidden="true"></i><span dir="ltr"><?= e($ct_phone) ?></span></a>
        <?php endif; ?>
        <?php if ($ct_email): ?>
        <a href="mailto:<?= e($ct_email) ?>"><i class="fas fa-envelope" aria-hidden="true"></i><span dir="ltr"><?= e($ct_email) ?></span></a>
        <?php endif; ?>
        <?php if ($ct_email2): ?>
        <a href="mailto:<?= e($ct_email2) ?>"><i class="fas fa-envelope" aria-hidden="true"></i><span dir="ltr"><?= e($ct_email2) ?></span></a>
        <?php endif; ?>
        <?php if ($ct_address): ?>
        <a href="<?= $nav_prefix ?>#contact"><i class="fas fa-map-marker-alt" aria-hidden="true"></i><span<?= bi_text($ct_address, $ct_address_en) ?>><?= e($ct_address) ?></span></a>
        <?php endif; ?>
      </div>
    </div>

    <div class="footer-bottom">
      <span<?= bi_text('© ' . date('Y') . ' ' . $footer_text, $footer_text_en !== '' ? '© ' . date('Y') . ' ' . $footer_text_en : '') ?>>&copy; <?= date('Y') ?> <?= e($footer_text) ?></span>
      <span>
        <a href="page.php?slug=privacy" data-ar="سياسة الخصوصية" data-en="Privacy Policy">سياسة الخصوصية</a> ·
        <a href="page.php?slug=terms"   data-ar="الشروط والأحكام" data-en="Terms">الشروط والأحكام</a>
      </span>
    </div>
  </div>
</footer>

<!-- Floating WhatsApp + scroll-top -->
<a class="fab-wa" href="https://wa.me/<?= e($wa_number) ?>?text=<?= rawurlencode($wa_msg) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">
  <svg viewBox="0 0 448 512" fill="currentColor" aria-hidden="true" focusable="false"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 110.9L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
</a>
<button class="scroll-top" id="scrollTop" aria-label="Back to top"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m18 15-6-6-6 6"/></svg></button>

<script src="assets/js/script.js"></script>
