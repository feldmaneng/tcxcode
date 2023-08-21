set -x
cd ~/tcxscode
git fetch origin
git reset --hard origin/master

# if the submodule directories are empty try:
# git submodule update --init

# ref http://stackoverflow.com/questions/5828324/update-git-submodule-to-latest-commit-on-origin

git submodule update --remote --merge
