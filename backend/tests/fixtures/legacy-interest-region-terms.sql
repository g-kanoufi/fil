-- Minimal grabba_tax_area terms for interest region sync tests.

INSERT INTO `fil_terms` (`term_id`, `name`, `slug`, `term_group`) VALUES
(2, 'United States', 'usa', 0),
(3, 'Alabama', 'al', 0),
(380, 'California - Southern', 'california-southern', 0);

INSERT INTO `fil_term_taxonomy` (`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES
(2, 2, 'grabba_tax_area', '', 0, 0),
(3, 3, 'grabba_tax_area', '', 2, 10),
(380, 380, 'grabba_tax_area', '', 2, 5);
