-- ============================================================
-- AUTOSAV — Seed : Version initiale des CGU
-- ============================================================
INSERT INTO `sav_terms_versions`
  (`trv_version`, `trv_title`, `trv_content`, `trv_summary`, `trv_is_current`, `trv_published_at`, `trv_created_by`)
VALUES
  ('1.0',
   'Conditions Générales d\'Utilisation — Version 1.0',
   'En utilisant l\'application AUTOSAV, vous acceptez les présentes Conditions Générales d\'Utilisation (CGU).\n\n1. OBJET\nL\'application AUTOSAV est destinée à la gestion d\'un réseau professionnel automobile. Son accès est réservé aux professionnels habilités.\n\n2. ACCÈS AU SERVICE\nL\'accès est conditionné à la création d\'un compte valide et à la validation de votre adresse email. Chaque compte est personnel et non cessible.\n\n3. OBLIGATIONS DE L\'UTILISATEUR\nVous vous engagez à utiliser l\'application dans le cadre de votre activité professionnelle, à ne pas divulguer vos identifiants de connexion, et à respecter les règles de sécurité applicables.\n\n4. PROTECTION DES DONNÉES (RGPD)\nVos données personnelles sont collectées et traitées conformément à la réglementation RGPD. Vous disposez d\'un droit d\'accès, de rectification et d\'effacement de vos données via votre espace personnel.\n\n5. RESPONSABILITÉ\nL\'éditeur ne saurait être tenu responsable des conséquences découlant d\'une utilisation non conforme de l\'application.\n\n6. MODIFICATION DES CGU\nLes CGU peuvent être modifiées à tout moment. Toute nouvelle version fera l\'objet d\'une acceptation explicite lors de votre prochaine connexion.\n\n7. LOI APPLICABLE\nLes présentes CGU sont régies par le droit français.',
   'Version initiale des conditions générales d\'utilisation.',
   1,
   NOW(),
   1)
ON DUPLICATE KEY UPDATE `trv_is_current` = 1;
