
# Todo

- (Potential) bugs
    - Sometimes: (probably test vaults are not generated completely distinct)
    
        There was 1 failure:
        
        1) Archivr\Test\VaultTest::testTwoPartySynchronization
        Failed asserting that 1502553467 matches expected 1502553466.
        
        /home/arne/Repositories/archivr/tests/VaultTest.php:204
        /home/arne/Repositories/archivr/tests/VaultTest.php:196
        /home/arne/Repositories/archivr/tests/VaultTest.php:177
        /home/arne/Repositories/archivr/tests/VaultTest.php:116
    - Deadlock prevention
        - Ensure same locking order of vaults
        - Same lockAdapter across multiple vaults?
- Features
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
    - Renamed ConnectionConfiguration to VaultConnection
    - Versionable (across archivr versions) Local/Remote index format
    - Separate OperationCollection building in own class/interface
