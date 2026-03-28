# 👥 Sistema de Gestión de Empleados
## Comparativa: Mala Práctica vs Patrones de Diseño

---

## 📁 ESTRUCTURA DE CARPETAS

```
gestion_empleados/
│
├── database/
│   └── schema.sql                  ← Importar primero en phpMyAdmin
│
├── mala_practica/                  ← ❌ Versión espagueti
│   ├── index.html                  ← Frontend (mismo para ambas)
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── app.js                  ← JS compartido
│   └── backend/
│       └── todo.php                ← TODO en un solo archivo "Dios"
│
└── buena_practica/                 ← ✅ Versión con patrones de diseño
    ├── index.html
    ├── css/
    │   └── style.css
    ├── js/
    │   └── app.js
    └── backend/
        ├── config/
        │   └── Database.php        ← Patrón SINGLETON
        ├── models/
        │   └── Empleado.php        ← Modelo de dominio puro
        ├── factories/
        │   └── EmpleadoFactory.php ← Patrón FACTORY
        ├── strategies/
        │   ├── SalarioStrategy.php      ← Interface (contrato)
        │   ├── SalarioTiempoCompleto.php← Patrón STRATEGY
        │   ├── SalarioTiempoParcial.php ← Patrón STRATEGY
        │   └── SalarioContratista.php   ← Patrón STRATEGY
        ├── observers/
        │   ├── Observable.php           ← Trait Observable
        │   ├── NotificacionObserver.php ← Patrón OBSERVER (DB)
        │   └── LogObserver.php          ← Patrón OBSERVER (archivo)
        ├── repositories/
        │   └── EmpleadoRepository.php   ← Patrón REPOSITORY
        └── api/
            └── index.php                ← Punto de entrada limpio
```

---

## 🚀 INSTALACIÓN EN XAMPP

### Paso 1 — Copiar archivos
```
Copiar la carpeta completa "gestion_empleados" dentro de:
C:\xampp\htdocs\          (Windows)
/opt/lampp/htdocs/        (Linux)
/Applications/XAMPP/htdocs/ (Mac)
```

### Paso 2 — Importar base de datos
1. Abrir **http://localhost/phpmyadmin**
2. Clic en **"Importar"** (pestaña superior)
3. Seleccionar el archivo `database/schema.sql`
4. Clic en **"Continuar"**

### Paso 3 — Abrir las aplicaciones
| Versión | URL |
|---------|-----|
| ❌ Mala Práctica | http://localhost/gestion_empleados/mala_practica/ |
| ✅ Buena Práctica | http://localhost/gestion_empleados/buena_practica/ |

---

## 🎯 FUNCIONALIDADES INCLUIDAS

| Funcionalidad | Mala Práctica | Buena Práctica |
|---|---|---|
| Registrar empleados (3 tipos) | ✅ | ✅ |
| Calcular salario | ✅ | ✅ Strategy |
| Gestionar vacaciones | ✅ | ✅ |
| Generar reportes | ✅ | ✅ |
| Enviar notificaciones | ✅ | ✅ Observer |

---

## 🧩 PATRONES DE DISEÑO (Buena Práctica)

| Patrón | Archivo | Propósito |
|--------|---------|-----------|
| **Singleton** | `config/Database.php` | Una sola conexión a la BD |
| **Factory** | `factories/EmpleadoFactory.php` | Crear empleados con defaults |
| **Strategy** | `strategies/Salario*.php` | Fórmula de salario intercambiable |
| **Observer** | `observers/*.php` | Notificar eventos automáticamente |
| **Repository** | `repositories/EmpleadoRepository.php` | Acceso a datos centralizado |

---

## ❌ MALAS PRÁCTICAS (Mala Práctica)

- `todo.php`: Un solo archivo con +300 líneas haciendo TODO
- SQL Injection vulnerable (sin prepared statements)
- Variables de una letra: `$c`, `$r`, `$d`, `$a`, `$n`...
- Lógica de negocio, DB y presentación mezcladas
- Código duplicado (cálculo de salario copiado 3 veces)
- N+1 queries en el reporte
- Sin validación de datos de entrada
- Magic numbers sin constantes (`0.15`, `40`, `4`)
- Variables globales (`global $conexion`)

---

## 🔧 REQUISITOS
- XAMPP con PHP 8.0+ y MySQL 5.7+
- Navegador moderno (Chrome, Firefox, Edge)
