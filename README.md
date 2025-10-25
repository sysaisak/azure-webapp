# Web App - Conectividad DMZ a PRD (Proyecto Aula)

Este repositorio contiene la aplicación de prueba PHP para el **Ejercicio 3** del proyecto de Arquitectura en la Nube.

El objetivo es validar la **Conexión Operativa** y la **Accesibilidad Segura** de un servicio PaaS (Azure App Service) a bases de datos privadas (Flexible Servers).

## Estructura de la Arquitectura

1.  **Web App:** Desplegada y con **VNet Integration** a la subred **DMZ** (`10.1.x.x`).
2.  **Bases de Datos (MySQL/PostgreSQL):** Residen en subredes dedicadas de **VNET-PRODUCCION** (`10.2.x.x`).
3.  **Flujo de Tráfico:** El tráfico de la aplicación web sale de la DMZ y cruza el VNet Peering hacia PRD.

## Reglas de Seguridad Validadas por la Aplicación

La conexión exitosa valida que:

* **NSG-DMZ (Salida):** Permite el tráfico saliente de la DMZ hacia PRD en los puertos `3306` (MySQL) y `5432` (PostgreSQL).
* **NSG-PRD (Entrada):** Permite el tráfico entrante de la DMZ a los puertos `3306` y `5432` de las subredes de base de datos (incluso si de momento están sin NSG por testeo).

## Configuración Requerida en Azure App Service

Para que la aplicación PHP pueda conectarse a la red privada, se deben configurar las siguientes **Variables de Entorno** (Application Settings) en tu Web App:

| Variable | Descripción |
| :--- | :--- |
| `MYSQL_HOST` | FQDN interno del servidor MySQL (Ej: `mysql-prd-isak.mysql.database.azure.com`) |
| `MYSQL_USER` | Usuario de administración de MySQL |
| `MYSQL_PASS` | Contraseña de MySQL |
| `PG_HOST` | FQDN interno del servidor PostgreSQL |
| `PG_USER` | Usuario de administración de PostgreSQL |
| `PG_PASS` | Contraseña de PostgreSQL |