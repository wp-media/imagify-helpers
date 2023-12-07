#!/usr/bin/env bash
root_dir=$PWD

if [ -d "../imagify" ]; then
  IMAGIFY_DIR="../imagify/"
elif [ -d "../imagify-plugin" ]; then
  IMAGIFY_DIR="../imagify-plugin/"
else
  echo "Imagify directory is not detected, make sure you add it to the directory `imagify` or `imagify-plugin`";
  exit 1
fi

rm -f imagify.zip

echo "Copy Imagify to temp"
mkdir -p imagify-tmp/imagify/
rsync -av $IMAGIFY_DIR imagify-tmp/imagify --exclude node_modules --exclude vendor --exclude bin --exclude Tests --exclude .git --exclude .github --exclude .tx --exclude .wordpress-org --exclude _dev --quiet

echo "Move working directory to temp one"
cd imagify-tmp/imagify
echo "Start composer"
composer install --no-dev --no-scripts --no-interaction --quiet

echo "Install npm packages"
export NVM_DIR=$HOME/.nvm;
source $NVM_DIR/nvm.sh;
nvm use 16
npm install --silent

echo "Build assets"
npm run build --silent --force

echo "Build compressed file"
cd ../
zip -r "$root_dir/imagify.zip" imagify -x "*/.*" "*/gruntfile.js" "*/composer.*" "*/node_modules/*" "*/package*" "*/php*" --quiet

cd ../
rm -rf imagify-tmp
