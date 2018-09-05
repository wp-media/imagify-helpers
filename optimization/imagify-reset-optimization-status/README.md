# Imagify | Reset Optimization Status

Will “reset” Imagify’s optimization status in the database, so that previously optimized images will be considered not optimized. Physical image files will not actually be modified!

⚠️ How to use:
1. Activate plugin.
2. Reload plugin page once.
3. **Deactivate plugin!** If you don’t deactivate the plugin, Imagify will be resetted again and again on each admin page load.

To be used with:
* any setup where physical image files have been modified on the file system directly (via FTP) and now need to be optimized
* any setup where Imagify should “forget” that it has already optimized images (quota will not be reset, of course)

Last tested with:
* Imagify 1.8.1.x
* WordPress 4.9.x
