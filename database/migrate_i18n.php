<?php
/* ============================================================
   ITX — i18n migration
   Adds English (_en) columns to the content tables. Idempotent:
   safe to run multiple times (checks information_schema first).
   Run once:  php database/migrate_i18n.php
   ============================================================ */
require __DIR__ . '/../config/db.php';

$pdo = getDB();
$dbName = DB_NAME;

$columns = [
    'hero_section'    => [
        'title_en'     => 'VARCHAR(255) NULL',
        'subtitle_en'  => 'TEXT NULL',
        'note_en'      => 'VARCHAR(350) NULL',
        'btn1_text_en' => 'VARCHAR(100) NULL',
        'btn2_text_en' => 'VARCHAR(100) NULL',
    ],
    'about_section'   => [
        'heading_en' => 'VARCHAR(255) NULL',
        'content_en' => 'TEXT NULL',
        'skills_en'  => 'TEXT NULL',
    ],
    'services'        => [
        'title_en'       => 'VARCHAR(200) NULL',
        'description_en' => 'TEXT NULL',
    ],
    'statistics'      => [
        'label_en' => 'VARCHAR(200) NULL',
    ],
    'testimonials'    => [
        'content_en'     => 'TEXT NULL',
        'author_role_en' => 'VARCHAR(150) NULL',
    ],
    'contact_info'    => [
        'address_en' => 'VARCHAR(350) NULL',
    ],
    'blog_categories' => [
        'name_en' => 'VARCHAR(150) NULL',
    ],
    'blog_posts'      => [
        'title_en'            => 'VARCHAR(300) NULL',
        'excerpt_en'          => 'TEXT NULL',
        'content_en'          => 'LONGTEXT NULL',
        'meta_title_en'       => 'VARCHAR(300) NULL',
        'meta_description_en' => 'TEXT NULL',
    ],
    'content_pages'   => [
        'title_en'   => 'VARCHAR(255) NULL',
        'content_en' => 'LONGTEXT NULL',
    ],
    'categories'      => [
        'name_en' => 'VARCHAR(100) NULL',
    ],
    'projects'        => [
        'title_en'       => 'VARCHAR(200) NULL',
        'short_desc_en'  => 'VARCHAR(350) NULL',
        'description_en' => 'TEXT NULL',
    ],
    'project_media'   => [
        'caption_en' => 'VARCHAR(250) NULL',
    ],
];

$check = $pdo->prepare(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?"
);

$added = 0; $skipped = 0;
foreach ($columns as $table => $cols) {
    foreach ($cols as $col => $def) {
        $check->execute([$dbName, $table, $col]);
        if ((int)$check->fetchColumn() > 0) {
            echo "skip  $table.$col (exists)\n";
            $skipped++;
            continue;
        }
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
        echo "ADD   $table.$col $def\n";
        $added++;
    }
}

echo "\nDone. Added $added column(s), skipped $skipped existing.\n";
