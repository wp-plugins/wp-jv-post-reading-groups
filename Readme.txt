=== WP JV Post Reading Groups ===
Contributors: Janos Ver
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JNF92QJY4PGGA&lc=HU&item_name=WP%20JV%20Post%20Reading%20Groups%20%2d%20Plugin%20Donation&item_number=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: access, login, permission, permissions, post, posts, privacy, private, restrict, simple, user, users
Requires at least: 3.9.2
Tested up to: 4.0
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Grant read-only permission for selected users (with no administrator role) on selected private posts.

== Description ==
Have you ever wanted to create separate section for different kind of users?

This plugin will enable you to

* Create Reading Groups, associate users and private posts with one or multiple groups

* Once these users logged in they will see those private posts which you granted access

* Publish your post to public as usual

Your users will not be aware (unless you tell them) of what Reading Groups they are part of (if any).

I have to admit that there are other plugins out there which let you setup your WordPress site like this, but this Plugin is way much simpler to setup and use than general purpose robust permission management systems.

**For example** you create Friends and Family Reading Groups. Then you create some users (Mom, Dad, John, Nancy) and assign these Reading Groups as follows: 
Friends: John, Nancy 
Family: Mom, Dad 

When you move to London you write posts about it:

* and you attach some nice pics about the city - you publish this post to general public (no magic, do it as usual)

* and you attach some nice pics about the flat you just rented - you want to grant access to your Friends and Family only (so you publish this post privately and check the Friends and Family Reading Group checkboxes)

* including your address – you don’t want to tell your address to anyone so you want to grant access to your Family only (so you publish it privately and check the Family Reading Group checkbox only)

As a result

* Mom and Dad will have access to all 3 posts  (about the city, the flat you rented and your address) after they logged in

* John and Nancy will be able to read your posts about the city and the flat you rented when they are logged in

* All your other friends will see your post about the city

That is it.

== Installation ==

1. Download wp-jv-post-reading-groups.zip
2. Extract to `/wp-content/plugins/wp-jv-post-reading-groups` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to create Reading Groups? =

Go to Settings -> Reading to create your Reading Groups.

= How to assign Reading Groups to Users? =

Go to Users -> All Users and select a non-admin user (admins have access to all RGs anyway) and grant permissions to Reading Groups of your choice. 

When you add a New User you will be able to select Reading Groups as well.

= How to assign Reading Groups to Posts? =

Go to Posts -> Edit Post and select who will be able to read that post. **Don't forget to publish that post privately.**

== Screenshots ==

1. Settings -> Reading
2. Users -> All Users
3. Users -> Add New
4. Posts -> All Posts
5. Posts -> Add New

== Changelog ==

= 1.2 =
* Fix issue: WP DB prefix to run on any instance

= 1.1 =
* Fix issue: click on a private post resulted in HTTP 404
* Fix issue: private posts where not included in recent posts widget
* Fix issue: "Private:" text from title was not excluded properly for languages other than English

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0 =
* Initial release

= 1.1 =
* Fix issue: click on a private post resulted in HTTP 404
* Fix issue: private posts where not included in recent posts widget
* Fix issue: "Private:" text from title was not excluded properly for languages other than English