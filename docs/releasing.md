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
./bin/build-release.sh 1.0.2 /path/to/output
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

For the version 1.0.2 button-label release:

1. Confirm the existing `v1.0.0` and `v1.0.1` tags and releases remain unchanged.
2. Create an annotated `v1.0.2` tag on the exact verified commit.
3. Push the commit and tag to GitHub.
4. Create a GitHub Release and attach the verified manual-install ZIP.
5. Confirm Packagist updates `haroone/module-link-guest-order-to-customer` from the GitHub tag.
6. Confirm Packagist exposes `1.0.2` and test `composer require haroone/module-link-guest-order-to-customer:^1.0.2` in a disposable Magento installation.

A GitHub Release is useful for people downloading the module manually, but Packagist requires the Git tag rather than a GitHub Release. Do not push a tag created before the release commit was verified.

Do not publish directly from a Windows `Compress-Archive` result.

The `Release package` GitHub Actions workflow performs the Linux build and creates or updates the GitHub release when a verified tag is pushed. It can also be dispatched with an existing tag to repair a missing release asset.
