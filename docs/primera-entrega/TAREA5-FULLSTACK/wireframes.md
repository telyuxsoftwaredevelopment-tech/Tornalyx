# Wireframes – Tornalyx SGDM
## TAREA 5 – Programación Full Stack | Primera Entrega

Representación esquemática (wireframe) de las vistas principales del sistema.  
Dispositivos: Desktop (≥1024px) y Mobile (≤640px).  
Convención: `[ ]` = contenedor, `[BTN]` = botón, `[IMG]` = imagen, `(===)` = separador.

---

## 1. index.html — Landing / Inicio

### Desktop

```
┌─────────────────────────────────────────────────────────────┐
│  [LOGO] Tornalyx   Soluciones  Módulos  Cómo  Roles     [Entrar] [Ver torneos]  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│                   [LOGO GRANDE]                             │
│           Torneos · deporte · mente · esports               │
│                                                             │
│         Un sistema.                                         │
│         Cualquier competencia.                              │
│                                                             │
│    Liga, eliminación directa o suizo. Del club de barrio    │
│    a la liga online — en una sola plataforma.               │
│                                                             │
│         [ Ver torneos ]    [ Crear cuenta → ]               │
│                                                             │
│    [Liga ●]  [Eliminación directa]  [Sistema suizo]         │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│  Para cada organización                                     │
│  Una plataforma, muchas competencias                        │
│                                                             │
│  [ Clubes ] [ Instituciones ] [ Federaciones ] [ Esports ]  │
│                                                             │
│  ┌───────────────────┐   ┌─────────────────────────────┐   │
│  │ Clubes deportivos  │   │  [mockup tabla posiciones]   │   │
│  │ Liga interna...   │   │   Liga Apertura · Zona A     │   │
│  │ [Liga ●][Resultados]  │   │   1. Halcones FC    19 pts  │   │
│  └───────────────────┘   └─────────────────────────────┘   │
├─────────────────────────────────────────────────────────────┤
│  Módulos disponibles                                        │
│  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐  ┌──────┐        │
│  │ Fútbol│  │Ajedrez│  │Básquet│  │Esports│  │+ Más │        │
│  └──────┘  └──────┘  └──────┘  └──────┘  └──────┘        │
├─────────────────────────────────────────────────────────────┤
│  Cómo funciona  (3 pasos)                                   │
│  ① Creá  →  ② Inscribite  →  ③ Competí                     │
├─────────────────────────────────────────────────────────────┤
│  Estadísticas  │  +1.200 torneos  │  +8.400 jugadores  │   │
├─────────────────────────────────────────────────────────────┤
│  Elegí tu rol                                               │
│  ┌──────────────┐   ┌──────────────┐   ┌──────────────┐   │
│  │ 🏅 Participante│   │ 🎯 Organizador│   │ 🛡 Admin      │   │
│  └──────────────┘   └──────────────┘   └──────────────┘   │
├─────────────────────────────────────────────────────────────┤
│  La plataforma que organiza todo  [ Comenzar gratis ]       │
├─────────────────────────────────────────────────────────────┤
│ [LOGO]  Tornalyx             Plataforma │ Módulos │ Empresa │
│ La plataforma que             Torneos     Fútbol    Nosotros │
│ organiza todo.                Mi perfil   Ajedrez   Contacto │
│                               Dashboard   Esports            │
│ © 2026 Tornalyx                                             │
└─────────────────────────────────────────────────────────────┘
```

### Mobile

```
┌─────────────────────┐
│ [LOGO] Tornalyx  [☰]│
├─────────────────────┤
│  [MOBILE MENU]      │
│  Soluciones         │
│  Módulos            │
│  Cómo funciona      │
│  [ Ver torneos ]    │
├─────────────────────┤
│   [LOGO GRANDE]     │
│  Torneos · deportes │
│                     │
│  Un sistema.        │
│  Cualquier          │
│  competencia.       │
│                     │
│  [ Ver torneos ]    │
│  [ Crear cuenta ]   │
├─────────────────────┤
│  [Liga●][Elim.][Sui]│
├─────────────────────┤
│  Una plataforma,    │
│  muchas competencias│
│  [Clubes][Instit.]  │
│  [Fed.  ][Esports]  │
│  ─────────────────  │
│  [mockup tabla]     │
└─────────────────────┘
```

---

## 2. login.html — Iniciar sesión

### Desktop

```
┌─────────────────────────────────────────────────────────────┐
│  [LOGO] Tornalyx                    [Crear cuenta] [Registrarse] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│              ┌─────────────────────────┐                   │
│              │      [LOGO 80px]        │                   │
│              │     Iniciar sesión      │                   │
│              │  Ingresa tus credenciales│                  │
│              │                         │                   │
│              │  ┌─────────────────────┐│                   │
│              │  │ Correo electrónico  ││                   │
│              │  │ [___________________]│                   │
│              │  └─────────────────────┘│                   │
│              │                         │                   │
│              │  Contraseña   ¿Olvidaste?│                  │
│              │  [___________________👁]│                   │
│              │                         │                   │
│              │  ☐ Recordar mi sesión   │                   │
│              │                         │                   │
│              │  [ Iniciar sesión ]     │                   │
│              │  ───────── o ───────── │                   │
│              │  ¿No tenés cuenta?      │                   │
│              │  [Registrate gratis]    │                   │
│              └─────────────────────────┘                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. registro.html — Crear cuenta (stepper 2 pasos)

### Desktop

```
┌─────────────────────────────────────────────────────────────┐
│  [LOGO] Tornalyx                    [Iniciar sesión] [Entrar] │
├─────────────────────────────────────────────────────────────┤
│               Crear cuenta  |  Únete en 2 pasos             │
│                                                             │
│         ①──────────────────────②                           │
│    [Datos personales]   →   [Tu rol]                        │
│                                                             │
│         ┌──────────────────────────────┐                   │
│         │  Datos personales            │                   │
│         │                              │                   │
│         │  [Nombre]    [Apellido]       │                   │
│         │  [Correo electrónico]         │                   │
│         │  [Contraseña]                │                   │
│         │  ████░░░░  Fuerza: Media     │                   │
│         │  [Confirmar contraseña]      │                   │
│         │  [Fecha de nacimiento]       │                   │
│         │                              │                   │
│         │  [ Continuar → ]             │                   │
│         └──────────────────────────────┘                   │
│                                                             │
│         ┌──────────────────────────────┐  (paso 2)         │
│         │  ¿Cuál es tu rol?            │                   │
│         │                              │                   │
│         │  ┌────────┐  ┌────────┐     │                   │
│         │  │🏅 Part.│  │🎯 Org. │     │                   │
│         │  └────────┘  └────────┘     │                   │
│         │  [ ← Volver ]  [ Registrarse]│                   │
│         └──────────────────────────────┘                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 4. torneos.html — Buscar torneos

### Desktop

```
┌─────────────────────────────────────────────────────────────┐
│  [LOGO]  nav...                            [Entrar][Registrarse] │
├─────────────────────────────────────────────────────────────┤
│  Buscá torneos                                              │
│  Encontrá la competencia ideal                              │
├─────────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────────────────────────┐  │
│  │ [🔍 Buscar torneo...]  [Disciplina▼] [Formato▼]       │  │
│  │ [Estado▼]  [Ordenar▼]                    [Limpiar]    │  │
│  └───────────────────────────────────────────────────────┘  │
│  Mostrando 12 torneos                                       │
│                                                             │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐          │
│  │[banner img] │ │[banner img] │ │[banner img] │          │
│  │ ● EN CURSO  │ │ PRÓXIMO     │ │ FINALIZADO  │          │
│  │ Copa Fútbol │ │ Torneo Ajedrez│ │ Esports Cup│          │
│  │ 🎮 Liga    │ │ ♟ Suizo     │ │ 🕹 Elim.   │          │
│  │ 📅 Jun 2026 │ │ 📅 Jul 2026 │ │ 📅 May 2026 │          │
│  │ 16 equipos  │ │ 32 jugadores│ │ 64 jugadores│          │
│  │ [Ver torneo]│ │[Ver torneo] │ │[Ver torneo] │          │
│  └─────────────┘ └─────────────┘ └─────────────┘          │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐          │
│  │   ...       │ │   ...       │ │   ...       │          │
│  └─────────────┘ └─────────────┘ └─────────────┘          │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. torneo-detalle.html — Detalle de torneo

### Desktop

```
┌─────────────────────────────────────────────────────────────┐
│  nav...                                                     │
├─────────────────────────────────────────────────────────────┤
│  [banner imagen torneo — ancho completo]                    │
│  ● EN CURSO  |  Copa Regional Fútbol 2026                   │
│  [Fútbol] [Liga] [16 equipos]   Organiza: Carlos García     │
├─────────────────────────────────────────────────────────────┤
│  [ Información ] [ Fixture ] [ Posiciones ] [ Resultados ]  │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────┐  ┌───────────────────────┐    │
│  │  Descripción            │  │  Inscripción          │    │
│  │  Lorem ipsum...         │  │  Inicio: 01/06/2026   │    │
│  │                         │  │  Cierre: 15/06/2026   │    │
│  │  Formato: Liga          │  │  Costo: Gratuito      │    │
│  │  Sistema: Todos vs todos│  │  [ Inscribirse ]      │    │
│  │  Puntos: 3/1/0          │  └───────────────────────┘    │
│  └─────────────────────────┘                               │
│                                                             │
│  Tabla de posiciones (panel activo)                         │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  # │ Equipo         │ PJ │ PG │ PE │ PP │ GF │ Pts │   │
│  │  1 │ Halcones FC    │  7 │  6 │  1 │  0 │ 22 │ 19 │   │
│  │  2 │ Real Norte     │  7 │  5 │  1 │  1 │ 18 │ 16 │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. admin/dashboard.html — Panel Administrador

### Desktop

```
┌────────────────┬────────────────────────────────────────────┐
│  [LOGO]        │  ☰  Panel de Administración         [👤]  │
│  ─────────────│────────────────────────────────────────────│
│  ≡ Inicio      │  Inicio                                    │
│  ≡ Torneos     │                                            │
│  ≡ Usuarios    │  ┌────────┐ ┌────────┐ ┌────────┐ ┌────┐  │
│  ≡ Crear       │  │  128   │ │  43    │ │  892   │ │ 12 │  │
│  ≡ Estadísticas│  │Torneos │ │ Activos│ │Jugador.│ │Pend│  │
│  ≡ Configuración│  └────────┘ └────────┘ └────────┘ └────┘  │
│                │                                            │
│  ─────────────│  Torneos recientes                         │
│  [avatar]      │  ┌─────────────────────────────────────┐  │
│  Carlos García │  │ Copa Fútbol 2026    ● EN CURSO       │  │
│  Administrador │  │ Torneo Ajedrez      PRÓXIMO          │  │
│                │  │ Esports Cup         FINALIZADO       │  │
│                │  └─────────────────────────────────────┘  │
│                │                                            │
│                │  Usuarios recientes                        │
│                │  ┌─────────────────────────────────────┐  │
│                │  │ [av] Carlos García   Org.   Activo  │  │
│                │  │ [av] María López     Part.  Activo  │  │
│                │  └─────────────────────────────────────┘  │
└────────────────┴────────────────────────────────────────────┘
```

### Mobile (sidebar cerrado)

```
┌─────────────────────┐
│ [☰] Panel Admin [👤]│
├─────────────────────┤
│ ┌─────┐ ┌─────┐    │
│ │ 128 │ │  43 │    │
│ │Torn.│ │Act. │    │
│ └─────┘ └─────┘    │
│ ┌─────┐ ┌─────┐    │
│ │ 892 │ │  12 │    │
│ │Play.│ │Pend.│    │
│ └─────┘ └─────┘    │
├─────────────────────┤
│ Torneos recientes   │
│ Copa Fútbol ● CURSO │
│ Torneo Ajedrez PROX │
└─────────────────────┘
```

---

## 7. organizador/dashboard.html — Panel Organizador

### Desktop

```
┌────────────────┬────────────────────────────────────────────┐
│  [LOGO]        │  ☰  Mis Torneos                     [👤]  │
│  ─────────────│────────────────────────────────────────────│
│  ≡ Mis torneos │  Mis torneos                               │
│  ≡ Crear       │                                            │
│  ≡ Participantes│  ┌────────┐ ┌────────┐ ┌────────┐        │
│  ≡ Fixture     │  │   5    │ │  127   │ │   2    │        │
│  ≡ Resultados  │  │Torneos │ │Inscrip.│ │Pendient│        │
│  ≡ Estadísticas│  └────────┘ └────────┘ └────────┘        │
│                │                                            │
│  ─────────────│  [ + Crear torneo ]                        │
│  [avatar]      │                                            │
│  Carlos García │  ┌─────────────────────────────────────┐  │
│  Organizador   │  │ Copa Fútbol 2026    ● EN CURSO  [→] │  │
│                │  │ Torneo Ajedrez      PRÓXIMO     [→] │  │
│                │  └─────────────────────────────────────┘  │
└────────────────┴────────────────────────────────────────────┘
```

---

## 8. participante/perfil.html — Mi Perfil

### Desktop

```
┌─────────────────────────────────────────────────────────────┐
│  nav...                                         [👤 Carlos] │
├─────────────────────────────────────────────────────────────┤
│  [banner hero perfil]                                       │
│  [avatar grande]  Carlos García                             │
│                   Participante  |  Montevideo, UY           │
│                   [ Editar perfil ]                         │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌─────────────────────────────────────────┐ │
│  │  Datos   │  │  Torneos jugados: 12                    │ │
│  │  ───────  │  │  Victorias: 7    Derrotas: 5           │ │
│  │  Nombre  │  │                                         │ │
│  │  Carlos  │  │  Historial                              │ │
│  │  García   │  │  ┌──────────────────────────────────┐  │ │
│  │          │  │  │ Copa Fútbol 2026   🥇 1° lugar   │  │ │
│  │  Email   │  │  │ Torneo Ajedrez     🥈 2° lugar   │  │ │
│  │  c@c.com │  │  │ Esports Cup        Particip.     │  │ │
│  │          │  │  └──────────────────────────────────┘  │ │
│  │  Rol     │  │                                         │ │
│  │  Part.   │  └─────────────────────────────────────────┘ │
│  └──────────┘                                              │
└─────────────────────────────────────────────────────────────┘
```

---

*Tornalyx SGDM | Wireframes – Primera Entrega | BT Tecnologías de la Información 2026*
