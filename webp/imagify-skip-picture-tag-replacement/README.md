# Imagify | Skip \<picture\> Tag Replacement

Excludes `<img>` tags from `<picture>` tag replacement for WebP display if they have either a data-skip-picture-replacement="yes" attribute or "skip-picture-replacement" class. Can be edited to target other classes for exclusion.

🚧 **ADVANCED CUSTOMIZATION, HANDLE WITH CARE!**

📝 **Manual code edit required before use!**

Add or change the items in the `$classes_to_skip` array to target whichever classes you need to.

Hint: Search for "EDIT_HERE" in the source code.

Documentation:
* [My Images Are Broken](https://imagify.io/documentation/my-images-are-broken/)

To be used with:
* Any setup where WebP image display is needed, using the "Use rewrite rules" option is not possible, and the "Use `<picture>` tags" option is causing some images not to display.

Last tested with:
* Imagify 1.9.x
* WordPress 5.6.x
