#!/bin/bash

# Get a list of files that have been added, copied, modified, renamed, or changed
files=$(git diff --diff-filter=ACMRTUXB --name-only origin/master)

# Filter the list of files to include only files with file extension $1
matched_files=$(echo "$files" | grep -E "(.$1$)")

# If there are no files with extension $1, exit with a status code of 0
if [ -z "$matched_files" ]; then
  echo "No $1 files detected in the git diff."
  exit 0
fi

# If there are unstaged changes, exit and prompt the user to stage all changes
if ! git diff --quiet; then
  echo -e "Unable to show a $1 Prettier diff because there are unstaged changes. Please stage all changes and re-run."
  exit 1
fi

# Check the formatting of the files
npx prettier --parser=$1 --check $matched_files

# If there are no issues, exit with a status code of 0
if [ $? -eq 0 ]; then
  echo "No issues detected."
  exit 0
fi

# Otherwise show a diff of the changes and exit with a status code of 1.
# This runs prettier (altering the files), runs a git diff against origin/master,
# and then restores the files to their original state.
npx prettier --parser=$1 --write --loglevel silent $matched_files
git --no-pager diff origin/master $matched_files
git -C $(git rev-parse --show-toplevel) restore .

echo -e "\n"
echo -e "These ^ files will be auto-formatted upon commit... or you can:"
echo -e "fix files individually: 'npx prettier --write \"<path-to-file>\"'"
echo -e "fix all files: 'git diff --diff-filter=ACMRTUXB --name-only origin/master | grep -E \"(.$1$)\" | xargs npx prettier --parser=$1 --write'"
exit 1
