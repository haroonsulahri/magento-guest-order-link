# Release Process

## Preconditions

- Work from a clean Git worktree.
- Confirm the release version and changelog.
- Run the complete validation guide in a disposable Magento environment.
- Confirm GitHub private vulnerability reporting is enabled and monitored.
- Confirm the package name is available in the intended Composer repository.

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

Do not publish directly from a Windows `Compress-Archive` result.
