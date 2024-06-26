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
 * If you write PHP code, it must adhere to PSR-12, except that we use tabs for indentation, not spaces.
 * Free contributions: Feel free to  to fork and submit a pull request with changes.
 * Please note this is a non-profit project with no paid staff. No contribution will be rewarded in money or in specie, but it will be duly credited. Likewise, your contributions will NOT be used for any commercial purpose.

### Pre-commit hook ###

You must run the complete test suite and build process locally before committing changes to git. To do so, make sure you have `make` and `docker` installed, set the environment variables 

You should include this code in `.git/hooks/pre-commit`.

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

make -s stop-all
make -s build-dev
make -s start
make -s start-db-for-test
make -s test
make -s test-acceptance
```

### Getting help ###

You can [open an issue in GitHub](https://github.com/isra00/neo-transposer/issues) or write me at neo-transposer@mail.com.
