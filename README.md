#### Versión 1 (MVP)

- botón Scan Images
- tabla con resultados
- detecta 404 / 403 / timeout

### V2.

- escaneo AJAX por lotes
- filtro por año y mes
- barra de progreso
- generación de 2 archivos Excel
- cálculo de porcentaje de error
- separación Broken vs Timeout



### NUEVOS PROBLEMAS DETECTADOS
1. Misma URL de imagen repetida en varias URL de la web:

post 1 -> imgbox.com/abc1.jpg  
post 2 -> imgbox.com/abc1.jpg  
post 3 -> imgbox.com/abc1.jpg  
post 4 -> imgbox.com/abc1.jpg  

Todas estas URL estarian caidas a la vez. 

2. Detecccion por porcentajes. Algunas URL tienen solo una imagen caida o quebrada. IGNORARLAS

- Pero otras tienen 2 o 3. Ignorarlas.
- Cuando son mas del 50% de la URL. Y solo un par de imagenes funciona... 

3. Problema de congelamiento por AJAX (escaneo que congelara Wordpress)

El codigo inicial hace esto: 

loop de todos los posts
→ request HTTP
→ request HTTP
→ request HTTP

Si tenemos 2000 post y 10 imagenes por post (lo cual es irreal ya que tenemos hasta 100+ por post) ... 10x2000 = 20k request... 

- Time out
- Congelar admin
- saturar servidor

SOLUCION: Escanear por Batch. Pequenos bloques. Batch1: 1-20. Batch2 21-40. Batch3 41-60. O bien por fechas especificas. Todo se realizara por Ajax.

4. Hay que agregar una barra de progreso. Necesitamos avisar cuanto falta,. si se quebro el analisis en algun punto. 

5. Export a formato excel. CSV





#### una NUEVA PROBLEMATICA
* y si hacemos un plugin que revise el contenido completo de <figures> para detectar si tienen lazy load las imagenes... 
* y dado que la mayoria no las tienen... entonces tenemos que actualizar toda la estructura... 