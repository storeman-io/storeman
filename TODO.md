
# Todo

- (Potential) bugs
    - Ensure non-conflicting OperationLists across vaults
    - Implement signal handler and ensure released locks on abortion
    - ensure blobIds are the same across vaults
    - PHP config
        - "bootstrap" project like
    - Deadlock prevention
        - (done) Ensure same locking order of vaults
        - Same lockAdapter across multiple vaults?
    - Nested archives
- Features
    - Extend index merging to handle modified file content and modified metadata separately
    - Implement rsync-like algorithm for DownloadOperation
    - Seekable container
    - Partial checkouts (only some subdirectory)
    - "Transactional/Atomic" file operations (temp + rename)
    - Detect file movement and add corresponding local operation
        - inode usage
    - Use full available mtime/ctime resolution
        - Keep comparison across systems with different resolutions in mind
    - Add "force-local" parameter to synchronization to be able to "keep" restored state
        - Automatically call after restore (can optionally be prevented)
    - More logging verbosity
        - Ask for confirmation on sync/restore commands
            - Mind --no-interaction option
        - (Injectable) EventManager (PSR-14)
    - Split large files in blob chunks
        - CSV UUIDs in index
        - Configuration per vault
    - Synchronization without archival
        - Delete old blobs on sync
    - Phar compilation
        - Installer ala getcomposer.org
    - WebDAV endpoint
    - GUI
        - WebApp?
    - Inotify watcher
    - Add version string during phar build
    - Check (php module, system, etc.) dependencies on runtime and offer installation help
    - Synchronization dry-run
    - Sanity-Checks
    - Use precomputed hashes (e.g. from zfs)
- Encryption
    - https://cryptomator.org/architecture/
    - Encryption/decryption as stream filter
        - https://stackoverflow.com/questions/27103269/what-is-a-bucket-brigade
        - https://github.com/bantuXorg/php-stream-filter-hash/blob/master/src/HashFilter.php
        - http://www.codediesel.com/php/creating-custom-stream-filters/
- Performance
    - Detect file moving using hash/mtime to uniquely identify file content
        - Copy/Move locally to prevent download
        - Reuse blobId to prevent update
    - Selective compression
        - Most media files are already compressed
- Code quality
    - Test (recursive) symlink support
    - Offer plugin/module integration
    - Versionable (across storeman versions) Local/Remote index format
    - Tests for phar compiler


## File hash usages

|Storage|Hash|
|---|---|
|S3|MD5|
|Backblaze|SHA1|
|FTP|_various_|
|Azure|MD5|
|Google Cloud Storage|CRC32C|
|ZFS|fletcher2, fletcher4, SHA256|
|Dropbox|[custom](https://www.dropbox.com/developers/reference/content-hash)|


