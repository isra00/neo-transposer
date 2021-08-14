[![Build Status](https://travis-ci.org/isra00/neo-transposer.svg?branch=master)](https://travis-ci.org/isra00/neo-transposer)

### Misión ###

Ayudar a los cantores del Camino en temas técnicos para que desarrollen más eficazmente su carisma.

### Visión ###

El cantor, especialmente el novato o con pocos conocimientos musicales, se ve incapaz de cantar muchos cantos que no están en un tono adecuado para su voz (especialmente mujeres). Sabe transportar acordes (o no), pero le cuesta encontrar un tono apropiado para su voz, lo cual le impide cantarlo bien o, directamente, cantarlo. Esta sencilla herramienta le ayuda a cantar dentro de su tesitura, desarrollando su carisma de cantor y animando la comunidad litúrgica para mayor gloria de Dios.

### Valores ###

 * No interferir en el carisma y costumbres del Camino.
 * No interferir en mi vocación.
 * Acceso universal: la aplicación se puede usar fácilmente desde todo tipo de dispositivos y conexiones, incluidos los precarios medios de África. Por eso es tecnología web abierta y no app nativa Android/iOS: https://blog.codinghorror.com/app-pocalypse-now/
 * Approach pedagógico: facilidad de uso pero informando de qué se está haciendo. Uso de lenguaje apropiado. Promover uso del Resucitó oficial.
 * Dad gratis lo que gratis habéis recibido: La aplicación será siempre gratuita.
 * Simplicidad.

### How to add a new language ###

 1. Create a new row in the table `book`
 2. Create a friendly URL for the song list at config[-dist].php (key `book_url`)
 3. Translate the interface with a `trans/[lang code].php` file
 4. Declare the new language in config[-dist].php (key `languages`)
 5. Fill the DB with the songs!
 6. Set songs for the Voice Wizard in config.wizard.php
 7. Translate the static pages
 8. Create a web/static/img/chords/unknown-[lang code].png image
 9. Create a web/static/img/mkt-[lang code].jpg image for the home page and invoke it from the stylesheet
 10. Create a web/static/img/flag-[lang code].jpg image for the home page and invoke it from the stylesheet
