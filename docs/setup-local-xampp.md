# Configuracion local con XAMPP

## 1. Copiar el proyecto

Mueve o copia esta carpeta a:

`C:\xampp\htdocs\ecobikemess`

## 2. Crear la base de datos local

En phpMyAdmin crea una base llamada:

`ecobikemess`

Luego importa el archivo:

`sql/ecobikemess.sql`

## 3. Crear tu archivo local de entorno

Copia `.env.example` y renombralo a:

`.env.local`

Ejemplo para XAMPP normal:

```env
APP_ENV=local
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=ecobikemess
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4
DB_TIMEZONE=-05:00
```

Si tu MySQL de XAMPP usa otro puerto, por ejemplo `3307`, cambia `DB_PORT`.

## 4. Encender servicios

Desde XAMPP inicia:

- Apache
- MySQL

## 5. Abrir el proyecto

En el navegador entra a:

`http://localhost/ecobikemess`

## 6. Notas utiles

- El proyecto ya no usa credenciales fijas de Hostinger en la conexion.
- En local ya no te forzara a `https`.
- Si cambias de entorno, solo ajustas `.env.local`.
- Los uploads siguen funcionando en la carpeta local `uploads/`.

## 7. Recomendacion de trabajo

Flujo sugerido:

1. Probar cambios en `localhost`.
2. Validar formularios, login, modales y rutas.
3. Cuando todo este bien, subir a hosting.
