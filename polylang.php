<?php
/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

pll_register_string (ChylyGDPRCookies::ID . '-intro', 'I Accept', ChylyGDPRCookies::ID);
pll_register_string (ChylyGDPRCookies::ID . '-intro', 'Show Purposes', ChylyGDPRCookies::ID);

foreach (ChylyGDPRCookiesView\Categories::$template as $i => $v) {
	pll_register_string (ChylyGDPRCookies::ID . '-categories', $v, ChylyGDPRCookies::ID);
}
pll_register_string (ChylyGDPRCookies::ID . '-categories', 'Privacy Policy Categories', ChylyGDPRCookies::ID);
pll_register_string (ChylyGDPRCookies::ID . '-categories', 'Analytics Enabled', ChylyGDPRCookies::ID);
pll_register_string (ChylyGDPRCookies::ID . '-categories', 'Marketing Enabled', ChylyGDPRCookies::ID);
pll_register_string (ChylyGDPRCookies::ID . '-categories', 'Agree to All', ChylyGDPRCookies::ID);
pll_register_string (ChylyGDPRCookies::ID . '-categories', 'Save', ChylyGDPRCookies::ID);
pll_register_string (ChylyGDPRCookies::ID . '-categories', 'Settings updated', ChylyGDPRCookies::ID);
