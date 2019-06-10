/**
 * @author Tomáš Chylý
 * @copyright Tomáš Chylý
 */

if (typeof (TCH) === 'undefined') {
	var TCH = {};
}

var wait = setInterval (function () {
	if (typeof (jQuery) !== 'undefined') {
		clearInterval (wait);

		(function ($) {
			if (typeof (TCH.Cookies) === 'undefined') {
				TCH.Cookies = {
					/**
					 * Get cookie.
					 * @return null|string
					 */
					Get (name) {
						name += "=";
						var decodedCookie = decodeURIComponent (document.cookie);
						decodedCookie = decodedCookie.split (';');
						for (var i = 0; i < decodedCookie.length; i++) {
							var row = decodedCookie [i].trim ();

							if (row.indexOf (name) === 0) {
								return row.substring (name.length, row.length);
							}
						}

						return null;
					},

					/**
					 * Set new cookie.
					 */
					Set (name, value, expirationInSeconds) {
						expirationInSeconds = parseInt (expirationInSeconds);

						var date = new Date ();
						date.setTime (date.getTime () + (expirationInSeconds * 1000));
						document.cookie = name + "=" + value + "; expires=" + date.toUTCString () + "; path=/";
					},

					/**
					 * Remove cookie.
					 */
					Remove (name) {
						document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
					}
				};
			}

			if (typeof (TCH.GDPRCookies) === 'undefined') {
				TCH.GDPRCookies = {
					settings: undefined, analyticsScripts: [], marketingScripts: [],

					/**
					 * GDPRCookies initialization.
					 */
					Init () {
						var self = this;

						if (typeof (TCH_GDPRCookies_settings) !== 'undefined') {
							this.settings = TCH_GDPRCookies_settings;
						}
						if (typeof (TCH_GDPRCookies_analyticsScripts) !== 'undefined') {
							this.analyticsScripts = TCH_GDPRCookies_analyticsScripts;
						}
						if (typeof (TCH_GDPRCookies_marketingScripts) !== 'undefined') {
							this.marketingScripts = TCH_GDPRCookies_marketingScripts;
						}

						var consent = $ ('#chyly-gdpr-cookies-consent');
						if (consent.length > 0) {
							if (typeof (this.settings) !== 'undefined' && parseInt (this.settings.general.type) === 1) {
								this.TypeJsCheckShouldIntro ();
							} else {
								setTimeout (function () {
									self.ShowPopup (consent, '#chyly-gdpr-cookies-consent');
								}, 500);
							}

							var body = $ ('body');
							body.on ('click', '#chyly-gdpr-cookies-consent-agree', function () {
								self.Agreed ($ (this));
							});
							body.on ('click', '#chyly-gdpr-cookies-consent-categories', function () {
								self.Purposes ($ (this));
							});
						}

						var agreeAll = $ ('#chyly-gdpr-cookies-categories-agree-all');
						if (agreeAll.length > 0) {
							$ ('body').on ('click', '#chyly-gdpr-cookies-categories-agree-all', function () {
								self.AgreeAll ();
							});
						}

						if (parseInt (this.settings.general.type) === 1) {
							this.TypeJSScripts ();
						}
					},

					/**
					 * Type Js check if should show Intro popup.
					 */
					TypeJsCheckShouldIntro () {
						var self = this;

						var elseAction = function () {
							TCH.Cookies.Set (self.settings.intro.cookie_intro_seen, "1", self.settings.intro.cookie_seen_lifespan);
							$ ('body').addClass ('chyly-gdpr-cookies-consent-validated');
						};

						var introSeen = TCH.Cookies.Get (this.settings.intro.cookie_intro_seen);
						if (introSeen !== "1") {
							$.ajax ({
								url: this.settings.geoip.request_url,
								success (response) {
									if (typeof (response) === 'string' && response !== '') {
										var json = null;

										try {
											json = $.parseJSON (response);
										} catch (exception) {
											console.error (exception);
										}

										if (json !== null && typeof (json.geoip) !== 'undefined') {
											if (json.geoip.isInEu) {
												self.ShowPopup ($ ('#chyly-gdpr-cookies-consent'), '#chyly-gdpr-cookies-consent');
											} else {
												elseAction ();
											}
										} else {
											elseAction ();
										}
									} else {
										elseAction ();
									}
								},
								error (response) {
									elseAction ();
								}
							});
						} else {
							elseAction ();
						}
					},

					/**
					 * Consent agreed.
					 */
					Agreed (thiss) {
						var self = this;

						$.ajax ({
							url: thiss.data ('href')
						}).always (function (response) {
							self.HidePopup ('#chyly-gdpr-cookies-consent');

							if (typeof window.App_static.Instance !== 'undefined') {
								window.location.reload ();
							} else if (typeof (response) === 'string' && response !== '') {
								var json = null;

								try {
									json = $.parseJSON (response);
								} catch (exception) {
									console.error (exception);
								}

								if (json !== null && typeof (json.scriptsHTML) !== 'undefined') {
									var body = $ ('body');

									for (var i = 0; i < self.analyticsScripts.length; i++) {
										try {
											body.append (atob (self.analyticsScripts [i]));
										} catch (exception) {
											console.error (exception);
										}
									}
									for (var i = 0; i < self.marketingScripts.length; i++) {
										try {
											console.log (self.marketingScripts [i]);
											body.append (atob (self.marketingScripts [i]));
										} catch (exception) {
											console.error (exception);
										}
									}

									body.append (atob (json.scriptsHTML));
								}
							}
						});
					},

					/**
					 * Wants to see purposes.
					 */
					Purposes (thiss) {
						var self = this;

						$.ajax ({
							url: thiss.data ('href')
						}).always (function (response) {
							self.HidePopup ('#chyly-gdpr-cookies-consent');

							if (typeof (response) === 'string' && response !== '') {
								var json = null;

								try {
									json = $.parseJSON (response);
								} catch (exception) {
									console.error (exception);
								}

								if (json !== null && typeof (json.href) !== 'undefined') {
									if (typeof window.App_static.Instance !== 'undefined') {
										window.App_static.Instance.NavigatePage (json.id);
									} else {
										window.location.href = json.href;
									}
								}
							}
						});
					},

					/**
					 * Show popup.
					 */
					ShowPopup (thiss, selector) {
						var body = $ ('body');
						var jDocument = $ (document);
						body.css ('top', '-' + jDocument.scrollTop () + 'px').data ('top', jDocument.scrollTop ());
						body.addClass ('noScroll');
						jDocument.scrollTop (0);

						selector = typeof (selector) !== 'undefined' ? selector : 'popup';

						$ (selector).css ('display', 'flex');
						$ (selector).css ('opacity', 0).animate ({opacity: 1}, 500);
					},

					/**
					 * Hide popup.
					 */
					HidePopup (selector) {
						var body = $ ('body');
						var jDocument = $ (document);
						body.removeClass ('noScroll');
						var scroll = Math.abs (parseFloat (body.data ('top')));
						jDocument.scrollTop (scroll);

						selector = typeof (selector) !== 'undefined' ? selector : 'popup';

						$ (selector).animate ({opacity: 0}, 500, function () {
							$ (selector).hide ();
						});
					},

					/**
					 * Agree to all categories and save.
					 */
					AgreeAll () {
						var form = $ ('#chyly-gdpr-cookies-categories-form');

						if (form.length > 0) {
							form.find ('.chyly-gdpr-cookies-checkbox').each (function () {
								this.checked = true;
							});
							form.find ('.submit').click ();
						}
					},

					/**
					 * Type Js add scripts based on consent.
					 */
					TypeJSScripts () {
						var self = this;

						$.ajax ({
							url: this.settings.scripts.request_url
						}).always (function (response) {
							if (typeof (response) === 'string' && response !== '') {
								var json = null;

								try {
									json = $.parseJSON (response);
								} catch (exception) {
									console.error (exception);
								}

								if (json !== null && typeof (json.scriptsHTML) !== 'undefined') {
									var body = $ ('body');

									var analytics = TCH.Cookies.Get (self.settings.intro.cookie_intro_analytics);
									var marketing = TCH.Cookies.Get (self.settings.intro.cookie_intro_marketing);

									if (analytics === "1") {
										for (var i = 0; i < self.analyticsScripts.length; i++) {
											try {
												body.append (atob (self.analyticsScripts [i]));
											} catch (exception) {
												console.error (exception);
											}
										}
									}
									if (marketing === "1") {
										for (var i = 0; i < self.marketingScripts.length; i++) {
											try {
												console.log (self.marketingScripts [i]);
												body.append (atob (self.marketingScripts [i]));
											} catch (exception) {
												console.error (exception);
											}
										}
									}

									body.append (atob (json.scriptsHTML));
								}
							}
						});
					}
				};

				/* On window load initialize. */
				jQuery (function () {
					TCH.GDPRCookies.Init ();
				});
			}
		}) (jQuery);
	}
}, 10);
