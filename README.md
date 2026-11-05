![example workflow](https://github.com/isra00/neo-transposer/actions/workflows/test.yml/badge.svg)

[![Contributor Covenant](https://img.shields.io/badge/Contributor%20Covenant-2.1-4baaaa.svg)](code_of_conduct.md) 

### Mission ###

Help neo-catechumenal cantors find the right song chords that fit their unique, personal voice range.

### Vision ###

Many cantors, especially women, cannot sing many songs which do not fit their voice range unless they transpose those songs. They may know how to transpose a song, but they struggle to find the right key which allows them to sing it well or just sing it, in many cases. The algorithm of Neo-Transposer measures their voice and calculates the right chords, so that they can sing every song, and develop their charisma for the _re-creation of the liturgical assembly_.

### Values ###

 * Be a help, not an obstacle, to the charisma and the customs of the Way.
 * Universal access: the app can be easily used with every device and connection, even in Africa. That's why this is open web technology and not a [native Android/iOS app](https://blog.codinghorror.com/app-pocalypse-now/)
 * Pedagogical approach: it must be easy to use and understand, but it must tell the user what's going on. Use the right musical terminology. Promote the use of the official songbook.
 * You received free, give free (Mt 10:8): the app will always be free to use and open source.
 * Simplicity in everything.

### Code of conduct ###

See [Code of Conduct](CODE_OF_CONDUCT.md)

### Contributing ###

 * Reporting: If you find any mistake or have an improvement suggestion, feel free to [open an issue in GitHub](https://github.com/isra00/neo-transposer/issues) or e-mail me (see contact below).
 * Working on accepted issues: you may start by the most highly prioritized tasks in the [Main Backlog](https://github.com/isra00/neo-transposer/projects/2) or the [Technical Backlog](https://github.com/isra00/neo-transposer/projects/1). You can submit your code via fork and pull request.
 * If you write JavaScript code, it may use jQuery's API, though we actually use zepto.js for better performance.
 * If you write PHP code, it must adhere to PSR-12 and the Pint rules defined in pint.json.
 * Free contributions: Feel free to fork and submit a pull request with changes.
 * Please note this is a non-profit project with no paid staff. No contribution will be rewarded in money or in specie, but it will be duly credited. Likewise, your contributions will NOT be used for any commercial purpose.

### Database changes ###

Schema changes (not data) are regular Laravel migrations; apply each one to `create_tables.sql` too and list it in its `migrations` table.

Changes in the database data related to songs (book, song, song_chord, unique_song) are tracked with Laravel migrations, as they are critically important for this application:

1. `make pull-song-data`: brings in prod-only edits. Only needed if songs were edited in prod. It needs `NT_PROD_SSH` set, and it refuses while prod has undeployed migrations.
2. Edit song data locally.
3. `make song-data-migration`: generates the guarded migration (each change only applies if prod still has the value it replaces) and refreshes `song_data.sql`.
4. `make test`: the test-all-transpositions may fail if the edits changed any song's transposition, and it lists those songs.
5. Review each difference and edit the JSON file `testAllTranspositions.expected.json` by hand where the change is intended.
6. Commit the migration, `song_data.sql` and the JSON together.
7. Upon deploy, run `php artisan migrate --force` in prod.

Note that `song_data.sql` is generated and must never be edited by hand.

### Pre-commit hook ###

You must run the complete test suite and build process locally before committing changes to git. To do so, make sure you have `make` and `docker` installed, set these environment variables 

```bash
#!/bin/sh

export NT_DB_HOST=host.docker.internal
export NT_DB_USER=root
export NT_DB_PASSWORD=root
export NT_DB_DATABASE=nt_prod
export NT_DB_TEST_DATABASE=nt_empty_tables
export NT_DEBUG=1
export NT_PROFILER=0
export NT_MAXMIND_LICENSE_KEY=[set your value]
```

Then run:

```bash
make -s stop-all
make -s build-dev
make -s start
make -s start-db-for-test
make -s test
make -s test-acceptance
```

Please add this process to your git pre-commit hook by running:

```bash
make install-hooks`.
```


### Getting help ###

You can [open an issue in GitHub](https://github.com/isra00/neo-transposer/issues) or write me at `neo-transposer@mail.com`.
