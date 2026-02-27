#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

read -r -p "Commit message: " COMMIT_MESSAGE
read -r -p "Version (e.g. 0.1.0 or v0.1.0): " VERSION_INPUT

if [[ -z "${COMMIT_MESSAGE//[[:space:]]/}" ]]; then
  echo "Error: commit message is required."
  exit 1
fi

if [[ -z "${VERSION_INPUT//[[:space:]]/}" ]]; then
  echo "Error: version is required."
  exit 1
fi

if [[ "$VERSION_INPUT" =~ ^v ]]; then
  TAG="$VERSION_INPUT"
else
  TAG="v$VERSION_INPUT"
fi

if git rev-parse "$TAG" >/dev/null 2>&1; then
  echo "Error: tag already exists: $TAG"
  exit 1
fi

CURRENT_BRANCH="$(git branch --show-current)"
if [[ -z "$CURRENT_BRANCH" ]]; then
  echo "Error: unable to detect current branch."
  exit 1
fi

if git ls-files --error-unmatch .env >/dev/null 2>&1; then
  echo "Error: .env is tracked by git. Remove it from index before release:"
  echo "  git rm --cached .env"
  exit 1
fi

git add -A
git commit -m "$COMMIT_MESSAGE"
git tag -a "$TAG" -m "Release $TAG"
git push origin "$CURRENT_BRANCH"
git push origin "$TAG"

echo "Release completed: commit pushed to '$CURRENT_BRANCH', tag '$TAG' created and pushed."
