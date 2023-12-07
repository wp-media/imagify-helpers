# Imagify Updater

Simple plugin that simulates the update process for imagify plugin.

## Build imagify zip file
1. Clone imagify and switch to the needed branch in a directory (called `imagify` or `imagify-plugin`) beside the directory containing this `compile.sh` file
2. Make sure that you have nodejs, npm and [nvm](https://github.com/nvm-sh/nvm) installed.
3. We are using node version 16 so make sure to run the command 
```
nvm install 16
```
4. Call the `compile.sh` file from terminal like
```
./compile.sh
```
5. This will compile assets and generate final zip file in your current directory, the file called `imagify.zip`

## Simulate the update process.

1. Open the file `imagify-updater/imagify-updater.php`
4. Change the new version constant `IMAGIFY_HELPER_UPDATE_NEW_VERSION` to the new version (this should be larger than the currently installed version)
5. Change the zip url for the new version (that we generated with the above build process and uploaded to any server to get the direct url)
6. Activate the plugin to see the update notice.
7. Once finishing update, you can deactivate the helper plugin and you don't need to clear transients here.
