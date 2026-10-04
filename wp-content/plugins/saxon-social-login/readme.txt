=== Saxon Social Login ===
Contributors: William Leonard, Saxon Enterprises, Inc.
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

"Sign in with Google" and "Sign in with Microsoft" buttons on the WordPress login screen, next to the normal username and password.

== Description ==

* Only existing users can sign in, except that members of the Google Workspace domains listed in SAXON_SSO_SIGNUP_DOMAINS get a Subscriber account the first time they sign in with Google. The account must be managed by that Workspace (Google's hd claim), not a personal Google account using a company address.
* Google: a verified Google address that matches a user's email signs that user in and connects the account on first use.
* Microsoft: Microsoft does not guarantee the email it reports is verified, so each user connects their Microsoft account once from Users, Profile, "Connected sign in accounts" while signed in. After that the button works.
* Users can connect or disconnect either account from their profile. Username and password sign in keeps working.
* OpenID Connect authorization code flow with PKCE, a one time state tied to the browser by a cookie, a nonce, and checks on issuer, audience and expiry. Other login checks hooked to `authenticate` still run.
* Plain PHP, no libraries, no settings screen. Nothing appears until a provider is configured.

== Setup ==

Redirect URI to register with both providers:

    https://YOUR-SITE/wp-json/saxon-sso/v1/callback

Google: Google Cloud console, APIs and Services, Credentials, Create credentials, OAuth client ID, type "Web application", add the redirect URI. Set the OAuth consent screen to Internal to allow only your Workspace users, or External for any Google account.

Microsoft: Microsoft Entra admin center (or Azure portal), App registrations, New registration, platform "Web", add the redirect URI. Then Certificates and secrets, New client secret, and copy its Value.

Add to wp-config.php, above "That's all, stop editing!":

    define( 'SAXON_SSO_GOOGLE_CLIENT_ID', '....apps.googleusercontent.com' );
    define( 'SAXON_SSO_GOOGLE_CLIENT_SECRET', '...' );
    define( 'SAXON_SSO_MICROSOFT_CLIENT_ID', '00000000-0000-0000-0000-000000000000' );
    define( 'SAXON_SSO_MICROSOFT_CLIENT_SECRET', '...' );
    define( 'SAXON_SSO_MICROSOFT_TENANT', 'common' ); // optional: common, organizations, consumers or a tenant id
    define( 'SAXON_SSO_SIGNUP_DOMAINS', 'example.com' ); // optional: Workspace domains whose members sign up as Subscribers

Either provider can be left out. Microsoft client secrets expire (up to 24 months); renew them before then.

If a page cache is in use, exclude wp-login.php and /wp-json/saxon-sso/.

== Uninstall ==

Deleting the plugin removes the connected account links. Deactivating keeps them.
