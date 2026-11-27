# API de integración de tiendas

La tienda crea el envío con `POST /api/v1/shipments.php`. EcoBike registra el paquete a nombre del cliente autenticado, genera la guía y devuelve `guide` y `tracking_url`. La tienda debe guardar la guía y mostrarla en la confirmación del pedido; puede enviar el enlace al comprador para que consulte el estado en EcoBike.

Cada cliente puede abrir **Mi perfil → Integración con la API → Generar / renovar clave**. La clave queda asociada a esa tienda, por lo que el mismo endpoint sirve para cualquier cliente y cada envío se registra en la cuenta propietaria de la clave. La clave completa aparece una sola vez; al renovarla, la anterior se desactiva.

EcoBike toma automáticamente `remitente_nombre` del nombre del emprendimiento y `remitente_telefono` y `remitente_direccion` del perfil de la tienda. Mantén esos datos actualizados en **Mi perfil**; no los envíes en el cuerpo de la solicitud.

## Autenticación

Envía la clave privada en `X-API-Key` (o como `Authorization: Bearer <clave>`). Usa siempre HTTPS y no pongas la clave en código que se ejecute en el navegador.

## Solicitud

Content-Type: `application/json`

`external_order_id` debe ser un identificador estable y único de la orden en la tienda. Reutilizarlo en un reintento devuelve la guía existente sin crear un segundo envío.

```json
{
  "external_order_id": "PED-10428",
  "observaciones_recoleccion": "Recoger en horario de tienda",
  "fecha_programada_recoleccion": "2026-12-01",
  "destinatario_nombre": "Nombre del comprador",
  "destinatario_telefono": "3101234567",
  "destinatario_direccion": "Carrera 1 # 2-30, Bogotá",
  "instrucciones_entrega": "Apto 301",
  "descripcion_contenido": "Prenda de vestir",
  "dimensiones_paquete": "0",
  "envio_mismo_dia": false,
  "zona_periferica": false,
  "recoger_cambios": false,
  "embalaje": false,
  "tiene_recaudo": false,
  "valor_recaudo": 0,
  "envio_destinatario": "no"
}
```

`dimensiones_paquete` acepta `0`, `2000`, `4000`, `7000`, `10000` o `12000`, correspondientes a las opciones del formulario de EcoBike para paquetes de hasta 49 cm. Para paquetes más grandes, solicita una cotización por WhatsApp. Las opciones booleanas se pueden enviar como `true` o `false`. El número de teléfono del destinatario debe tener 10 dígitos o ser `Flex`. La fecha de recolección es opcional y usa `YYYY-MM-DD`.

## Respuesta

Creación: HTTP `201`.

```json
{
  "success": true,
  "duplicate": false,
  "external_order_id": "PED-10428",
  "guide": "EBM-20261201-A1B2C3",
  "shipping_cost": 8000,
  "tracking_url": "https://tu-dominio.com/?guia=EBM-20261201-A1B2C3#rastreo",
  "message": "Envío creado. Comparte la guía y el enlace de rastreo con el comprador."
}
```

EcoBike calcula el costo con las mismas tarifas y recargos del formulario; no envíes `costo_total`. Los errores de validación responden HTTP `422`; una clave ausente o inválida responde HTTP `401`. La página pública usa la guía para mostrar el estado del envío.
