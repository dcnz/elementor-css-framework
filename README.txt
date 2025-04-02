Still a work in progress.

1. Install and activate.
2. Go to CSS Framework in the admin.
3. You can see the CSS variables pulled from assets/css/variables.css. If you edit and save changes, it updates them in the file.
4. If you go to Edit CSS Files you can edit the variables or section classes CSS files directly.
5. To use the classes, edit a page in Elementor and edit a container. Go to the Advanced tab and find the CSS Framework section and select the CSS class(es) you want to use.

Hope that makes sense.

Currently the section CSS doesn't automatically apply in the editor and I'm working on resolving that. It works on the front end fine. The reason it doesn't update in the editor is because of the multi-select and how it loads the css classes. I'm trying to get it to use Elementor's internal API but it's having issues.
