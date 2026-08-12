# Release Process

## Preconditions

- Work from a clean Git worktree.
- Confirm the release version and changelog.
- Run the complete validation guide in a disposable Magento environment.
- Confirm GitHub private vulnerability reporting is enabled and monitored.
- Confirm the package name is available in the intended Composer repository.
- Confirm no existing local or remote release tag points to a different commit.

## Build

Build on Linux or WSL so the ZIP uses portable path separators and permissions:

```bash
./bin/build-release.sh 1.0.0 /path/to/output
```

The builder excludes repository-only development files, normalizes directory and file permissions, verifies the ZIP and extracts it again for a portability and privacy check.

## Release verification

Before tagging or uploading:

1. Inspect `zipinfo -1` and confirm every entry uses `/` separators.
2. Extract the ZIP on Linux and confirm every directory is traversable and every file is readable.
3. Run `composer validate --strict --no-check-publish` against the extracted module.
4. Run `dev/check-release.php` against the extracted tree.
5. Install the extracted tree into a fresh supported Magento instance.
6. Run `setup:upgrade`, DI compilation, unit tests and the integration test.
7. Complete the functional matrix using generated records only.
8. Tag the exact verified commit and attach only the verified archive.

## Tagging and Packagist

Packagist reads versions from Git branches and tags. Do not add a `version` field to `composer.json`.

For the first stable release:

1. Create an annotated `v1.0.0` tag on the exact verified commit.
2. Push the commit and tag to GitHub.
3. Optionally create a GitHub Release and attach the verified manual-install ZIP.
4. Submit `https://github.com/haroonsulahri/magento-guest-order-link` to Packagist.
5. Enable or confirm automatic GitHub updates on the Packagist package page.
6. Confirm Packagist exposes `1.0.0` and test `composer require haroone/module-guest-order-link:^1.0` in a disposable Magento installation.

A GitHub Release is useful for people downloading the module manually, but Packagist requires the Git tag rather than a GitHub Release. Do not push a tag created before the release commit was verified.

Do not publish directly from a Windows `Compress-Archive` result.
