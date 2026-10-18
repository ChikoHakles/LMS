-- Data demo Ruang LMS untuk MySQL/MariaDB.
-- Jalankan setelah seluruh migration selesai pada database ruang_lms.
-- Akun awal (ganti kata sandi setelah login):
--   admin@ruang.test / RuangAdmin!2026
--   tutor@ruang.test / RuangTutor!2026
-- Insert hanya menambah data yang belum ada; akun dengan email yang sudah terpakai
-- tidak akan ditimpa.

USE ruang_lms;

START TRANSACTION;

SET @demo_now = CURRENT_TIMESTAMP;

INSERT INTO users (
    name, email, email_verified_at, password, remember_token,
    created_at, updated_at, role, status
)
SELECT
    'Admin Ruang', 'admin@ruang.test', @demo_now,
    '$2y$12$FxYWE7YJwT7WbZobFqKCPenTdoSKMBdKi/wTn7hHHKUZZXmz./MCu',
    NULL, @demo_now, @demo_now, 'admin', 'active'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@ruang.test'
);

INSERT INTO users (
    name, email, email_verified_at, password, remember_token,
    created_at, updated_at, role, status
)
SELECT
    'Tutor Biologi', 'tutor@ruang.test', @demo_now,
    '$2y$12$Sn0yyMeQ3FvMtuU8kxM8FueBojd9h04oTZDfU.f4xK/N.U4oCnbLK',
    NULL, @demo_now, @demo_now, 'tutor', 'active'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'tutor@ruang.test'
);

-- Artikel demo
INSERT INTO materials (
    type, title, summary, owner_id, status, published_at, blocks, created_at, updated_at
)
SELECT
    'article',
    'Mengenal Ekosistem',
    'Pengantar tentang komponen biotik, abiotik, dan hubungan di dalam ekosistem.',
    tutor.id,
    'published',
    @demo_now,
    '[{"type":"heading","text":"Apa itu ekosistem?","level":2},{"type":"paragraph","text":"Ekosistem adalah hubungan timbal balik antara makhluk hidup dan lingkungan tempatnya tinggal."},{"type":"heading","text":"Komponen ekosistem","level":2},{"type":"list","items":["Komponen biotik adalah makhluk hidup, seperti tumbuhan, hewan, dan mikroorganisme.","Komponen abiotik adalah unsur tak hidup, seperti air, tanah, udara, dan cahaya matahari."],"ordered":false},{"type":"paragraph","text":"Keseimbangan ekosistem terjaga melalui interaksi antara komponen biotik dan abiotik."}]',
    @demo_now,
    @demo_now
FROM users AS tutor
WHERE tutor.email = 'tutor@ruang.test'
  AND tutor.role = 'tutor'
  AND NOT EXISTS (
      SELECT 1 FROM materials AS existing
      WHERE existing.owner_id = tutor.id
        AND existing.type = 'article'
        AND existing.title = 'Mengenal Ekosistem'
  );

-- Video demo (ID YouTube Big Buck Bunny; ganti dengan ID video pembelajaran sendiri bila perlu)
INSERT INTO materials (
    type, title, summary, owner_id, status, published_at, youtube_video_id, created_at, updated_at
)
SELECT
    'video',
    'Video Demo Pembelajaran',
    'Contoh materi video untuk melihat tampilan pemutar video di LMS.',
    tutor.id,
    'published',
    @demo_now,
    'aqz-KE-bpKQ',
    @demo_now,
    @demo_now
FROM users AS tutor
WHERE tutor.email = 'tutor@ruang.test'
  AND tutor.role = 'tutor'
  AND NOT EXISTS (
      SELECT 1 FROM materials AS existing
      WHERE existing.owner_id = tutor.id
        AND existing.type = 'video'
        AND existing.title = 'Video Demo Pembelajaran'
  );

-- Kuis demo
INSERT INTO materials (
    type, title, summary, owner_id, status, published_at, created_at, updated_at
)
SELECT
    'quiz',
    'Kuis Dasar Ekosistem',
    'Latihan pilihan ganda dan esai tentang komponen ekosistem.',
    tutor.id,
    'published',
    @demo_now,
    @demo_now,
    @demo_now
FROM users AS tutor
WHERE tutor.email = 'tutor@ruang.test'
  AND tutor.role = 'tutor'
  AND NOT EXISTS (
      SELECT 1 FROM materials AS existing
      WHERE existing.owner_id = tutor.id
        AND existing.type = 'quiz'
        AND existing.title = 'Kuis Dasar Ekosistem'
  );

INSERT INTO quiz_questions (
    material_id, type, prompt, points, position, created_at, updated_at
)
SELECT material.id, 'choice',
       'Manakah yang termasuk komponen biotik dalam ekosistem?',
       10, 0, @demo_now, @demo_now
FROM materials AS material
JOIN users AS tutor ON tutor.id = material.owner_id
WHERE tutor.email = 'tutor@ruang.test'
  AND material.type = 'quiz'
  AND material.title = 'Kuis Dasar Ekosistem'
  AND NOT EXISTS (
      SELECT 1 FROM quiz_questions AS existing
      WHERE existing.material_id = material.id AND existing.position = 0
  );

INSERT INTO quiz_choices (question_id, label, is_correct, position, created_at, updated_at)
SELECT question.id, choice.label, choice.is_correct, choice.position, @demo_now, @demo_now
FROM (
    SELECT 0 AS position, 'Tumbuhan' AS label, 1 AS is_correct
    UNION ALL SELECT 1, 'Cahaya matahari', 0
    UNION ALL SELECT 2, 'Air', 0
    UNION ALL SELECT 3, 'Batu', 0
) AS choice
JOIN quiz_questions AS question
  ON question.prompt = 'Manakah yang termasuk komponen biotik dalam ekosistem?'
JOIN materials AS material ON material.id = question.material_id
JOIN users AS tutor ON tutor.id = material.owner_id
WHERE tutor.email = 'tutor@ruang.test'
  AND material.title = 'Kuis Dasar Ekosistem'
  AND question.position = 0
  AND NOT EXISTS (
      SELECT 1 FROM quiz_choices AS existing
      WHERE existing.question_id = question.id
        AND existing.position = choice.position
  );

INSERT INTO quiz_questions (
    material_id, type, prompt, points, position, created_at, updated_at
)
SELECT material.id, 'essay',
       'Jelaskan perbedaan komponen biotik dan abiotik, lalu berikan masing-masing satu contoh.',
       10, 1, @demo_now, @demo_now
FROM materials AS material
JOIN users AS tutor ON tutor.id = material.owner_id
WHERE tutor.email = 'tutor@ruang.test'
  AND material.type = 'quiz'
  AND material.title = 'Kuis Dasar Ekosistem'
  AND NOT EXISTS (
      SELECT 1 FROM quiz_questions AS existing
      WHERE existing.material_id = material.id AND existing.position = 1
  );

COMMIT;
