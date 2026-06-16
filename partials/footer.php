<?php
/* Shared footer (new ITX design) + floating WhatsApp + scroll-top + script.
   Requires: $logo_url, $site_name, $footer_text, $socials, $nav_prefix,
   $ct_phone, $ct_email, $ct_address, $wa_number, $wa_msg. */
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

      <div class="footer-col">
        <h4 data-ar="تواصل" data-en="Contact">تواصل</h4>
        <?php if ($ct_phone): ?><a href="tel:<?= e($ct_phone) ?>" dir="ltr" style="text-align:start"><?= e($ct_phone) ?></a><?php endif; ?>
        <?php if ($ct_email): ?><a href="mailto:<?= e($ct_email) ?>" dir="ltr" style="text-align:start"><?= e($ct_email) ?></a><?php endif; ?>
        <?php if ($ct_address): ?><a href="<?= $nav_prefix ?>#contact"<?= bi_text($ct_address, $ct_address_en) ?>><?= e($ct_address) ?></a><?php endif; ?>
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
  <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.2-1.7-.8-1.9-.9-.3-.1-.5-.2-.7.2s-.8.9-.9 1.1c-.2.2-.3.2-.6.1-1.7-.9-2.9-1.6-4-3.6-.3-.5.3-.5.8-1.6.1-.2 0-.4 0-.5s-.7-1.6-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.4-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.3 5.2 4.6 1.9.8 2.7.9 3.6.8.6-.1 1.7-.7 1.9-1.4.2-.7.2-1.2.2-1.4-.1-.2-.3-.2-.6-.4zM12 2a10 10 0 0 0-8.6 15l-1.4 5 5.1-1.3A10 10 0 1 0 12 2z"/></svg>
</a>
<button class="scroll-top" id="scrollTop" aria-label="Back to top"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m18 15-6-6-6 6"/></svg></button>

<script src="assets/js/script.js"></script>
