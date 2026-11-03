# Traducir Mapa de viajes a tu idioma

Todos los textos del mapa están en archivos de texto (`.ini`), uno por idioma. Hoy hay **español (es-ES)** e **inglés (en-GB)**. Si quieres añadir otro idioma, no hace falta saber programar: solo traducir.

## Lo que tienes que saber antes de empezar
- Cada idioma es una carpeta con el código del idioma, por ejemplo `fr-FR` (francés de Francia) o `pt-BR` (portugués de Brasil).
- El inglés (`en-GB`) es el idioma de referencia: tu traducción tiene que tener **los mismos archivos y las mismas claves**.
- Solo se aceptan idiomas **completos**: no vale traducir la mitad.
- Los textos que traduces se **muestran en la web de otras personas**, por eso se revisan antes de aceptarlos.

## Paso a paso

1. **Descarga el proyecto** (botón verde «Code» › «Download ZIP», o con Git). Necesitas PHP 8.1 o superior en tu ordenador solo para pasar la comprobación.
2. **Crea la plantilla de tu idioma.** En la carpeta del proyecto:
   ```
   php build/idiomas.php plantilla fr-FR
   ```
   Cambia `fr-FR` por tu código. Se crea la carpeta de tu idioma en las 6 extensiones, con los textos en inglés, listos para traducir.
3. **Traduce.** Abre cada archivo `.ini` de tu carpeta (hay 10) y cambia **solo lo que va entre comillas**:
   ```
   COM_MAPAVIAJES_NO_ALBUMS="There is no map available to you yet."
   ```
   pasa a ser, por ejemplo:
   ```
   COM_MAPAVIAJES_NO_ALBUMS="Aucune carte n'est disponible pour vous."
   ```
4. **Qué NO se toca**
   - El nombre de la clave (lo que está antes del `=`): `COM_MAPAVIAJES_NO_ALBUMS`.
   - Las comillas que rodean cada texto.
   - Los marcadores `%s`, `%d`, `%1$s`: son huecos que el programa rellena (un nombre, un número…). Tienen que aparecer en tu traducción **igual que en el inglés**, aunque cambies el orden de las palabras.
5. **Qué NO se puede escribir** (la comprobación lo rechaza): etiquetas HTML (`<b>`, `<script>`…), `javascript:`, direcciones de internet (`https://…`, `www.…`), manejadores como `onclick=` y entidades como `&lt;`. Si necesitas una comilla dentro de un texto, escríbela como `\"`.
6. **Comprueba tu traducción:**
   ```
   php build/idiomas.php comprobar fr-FR
   ```
   Si dice «correcta», estás listo. Si dice «HAY QUE ARREGLAR», te indica el archivo y la clave.
7. **Pruébala en Joomla** (opcional pero recomendable): empaqueta con `php build/build.php`, instala el ZIP de `dist/` y pon la web o tu usuario en tu idioma.
8. **Envíala por GitHub:** abre una «Pull request» con tu carpeta (o un «Issue» con la plantilla «Ofrecer una traducción» y adjunta los archivos). Pasa por la comprobación automática y por una revisión antes de entrar.

## Qué pasa después
- Si todo está bien, tu idioma entra y sale en **la siguiente versión** del paquete, con tu nombre en el apartado de créditos de `NOTICE.md` (si quieres aparecer).
- Si cambian los textos en una versión nueva, se avisará para actualizar las claves nuevas. Un idioma al que le falten claves no se incluye hasta completarlo.

## Si algo no sale
- «`no es un código de idioma válido`»: usa dos letras minúsculas, un guion y dos mayúsculas (`fr-FR`).
- «`no conserva los marcadores`»: en tu texto falta un `%s` o `%d` (o sobra). Míralo en el inglés.
- «`falta la clave`»: borraste una línea. Copia la del inglés y tradúcela.
- Más dudas: abre un «Issue». Para fallos de seguridad **no** uses un Issue público: mira [`CONTRIBUTING.md`](../CONTRIBUTING.md).
