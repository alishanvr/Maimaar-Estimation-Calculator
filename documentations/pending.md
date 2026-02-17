- i want to everything should setup from the admin side, including database details, email settings, etc. (everything that is in .env file) so that we can easily deploy the application on any server without changing the env file. please implement this functionality in the admin panel and ensure that all settings are stored securely and can be easily updated by the admin. also, provide documentation on how to use these settings and what each setting does.

- i want that admin should have option to remove / clear cache from admin side. please implement this functionality in the admin panel and ensure that it is secure and only accessible to authorized users. also, provide documentation on how to use this feature and what it does.
- i want that during installation and after the installation, admin should be able to run database migrations and seeders from the admin panel. please implement this functionality in the admin panel and ensure that it is secure and only accessible to authorized users. also, provide documentation on how to use this feature and what it does.
- i want that during installation, admin should be able to set up the application and database details from the admin panel. please implement this functionality in the admin panel and ensure that it is secure and only accessible to authorized users. also, provide documentation on how to use this feature and what it does. admin should have option to use file database , mysql, sqlite, postgresql or sql server as database. admin should also have option to set up email settings from the admin panel. please implement this functionality in the admin panel and ensure that it is secure and only accessible to authorized users. also, provide documentation on how to use this feature and what it does.
- **I want a workflow that I push to main and it auto deploy, auto migrate or other things**
- in some of my other laravel applications, i see storage suddenly have a large space used. i want to add a feature in admin panel to clear storage cache and also to see what is taking space in storage. please implement this functionality in the admin panel and ensure that it is secure and only accessible to authorized users. also, provide documentation on how to use this feature and what it does.
- i want that when setup run, then it creates a user that should not be deleteable or revokable or change passwordable. that will be used as super admin. only one way to reset password is login this account and change self. OR change from terminal. no other way. i want the username could be ali@wprobo.com and password could be randomly generated and sent to this email. please implement this functionality in the admin panel and ensure that it is secure and only accessible to authorized users. also, provide documentation on how to use this feature and what it does.



~~Branding & PDF Polish — Company logo in PDF headers, letterhead styling, custom fonts for the generated BOQ/JAF PDFs.~~
~~User Experience Polish — Estimation duplication/clone, version history/revision tracking, estimation comparison, bulk export.~~ DONE (Iteration 12)
~~Dashboard Enhancements — Charts over time (estimations per week/month), user activity heatmap, estimation value trends.~~
~~6. i want to display "fill test data" button ONLY if a specific query parameter is present in the URL. For example, if the URL contains `?fill_test_data=true`, then the button should be visible; otherwise, it should be hidden. Please implement this functionality in the frontend using JavaScript or any frontend framework we are using.~~
~~10. Can we add hints and other helpful information in the form of tooltips or popovers in the frontend to guide users through the application?~~
~~18. I request you to review documentations/web-php directory and compare it with our current implementation. Don't implement anything. just give me a table what's missing in our laravel app but available in web-php.~~
~~7. Admin can set settings for company logo, fav icons etc. and these settings should be reflected in the frontend.~~
~~8. I want to add robust/rich reporting features in the application, allowing users to generate and export reports based on various criteria. What are some recommended approaches or packages for implementing this functionality in Laravel?~~
~~12. There is no option to change and set a password for a user. please fix this.~~
    ~~14. I want to implement a feature that allows users to reset their passwords from front end.~~
~~13. Admin should not be able to revoke himself. Please add a check to prevent this action.~~
~~14. admin should be able to reset/change password for any user.~~
~~10. I need a print or download estimation button in admin side. When clicked, it should generate a PDF of the estimation details. What are some recommended libraries or approaches for generating PDFs in Laravel, and how can I ensure that the generated PDFs are well-formatted and include all necessary information?~~
~~17. I can not see add crane, mezzanine, etc. options in the estimation form. Please add these options to the form and ensure that they are properly saved in the database and displayed in the estimation details.~~
~~check file documentations/15-missing-features-gap-analysis.md missing features gap and build the followings:~~
~~- Product/Inventory Admin CRUD~~
~~- Product Search (frontend and backend/admin side)~~
~~- Analytics (frontend and backend/admin side)~~
~~- Reports/Export Log Table (backend/admin side)~~
~~- Session Tracking Table (backend/admin side)~~
~~- Analytics Aggregation Table (backend/admin side)~~
~~You should build the backend and frontend.~~
~~and in the end share how i can check/verify that the features are implemented correctly. give me steps to verify the features.~~
~~also, in the end share a table for everything that is missing and considered as CRUD from admin side. So, admin will have a full control of every aspect.~~
~~also analyze, if any of the features can be implemented in a more efficient way or if there are any potential issues with the implementation. Please implement and update accordingly.~~
~~Verify that the following features are available on frontend and on backend:~~
~~- Liner/Ceiling Calculator~~
~~- Fascia Type in Canopy Calculator~~
~~- RAWMAT Output Sheet~~
~~- Multi-Building Projects~~
~~11. Admin can edit any estimation generated by any user. When an estimation is edited, the changes should be logged into the activity log with details of what was changed, who made the change, and when it was changed. How can I implement this functionality in Laravel, and what are some best practices for logging such changes effectively?~~
~~15. I want to give dynamic and robust feature for currency to admin. So, they can set a default currency. when they set then all prices available in database should be converted to that currency and displayed in frontend. Please suggest how to implement this functionality in Laravel. For conversion, we have to ask the rate from admin or we can use any API for that.~~


---
16. We need to display all inventory items and other details to admin and they can set, add, remove etc.
5. how to host Laravel and frontend securely on the same domain with SSL on shared domain?
2. Do we need to set reverse proxy for Laravel to work on shared hosting?
3. What are the best practices for securing Laravel applications on shared hosting?
9. I need more details on recent activity widget. What kind of activities should be logged and displayed? Should it include user actions, system events, or both? How should the data be presented (e.g., table, list, etc.)?
