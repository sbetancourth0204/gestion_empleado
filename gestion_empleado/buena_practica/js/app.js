// =============================================================
// app.js — Frontend compartido para AMBAS versiones
// La única diferencia es la variable API_URL definida en index.html
// =============================================================

// ── TIPOS DE EMPLEADO ────────────────────────────────────────
const TIPO_LABELS = {
  tiempo_completo: { label: 'Tiempo Completo', cls: 'pill-tc' },
  tiempo_parcial:  { label: 'Tiempo Parcial',  cls: 'pill-tp' },
  contratista:     { label: 'Contratista',      cls: 'pill-ct' },
};
const ESTADO_LABELS = {
  pendiente: { label: 'Pendiente', cls: 'pill-pen' },
  aprobada:  { label: 'Aprobada',  cls: 'pill-ok'  },
  rechazada: { label: 'Rechazada', cls: 'pill-rej'  },
};

// ── INICIALIZACIÓN ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  setFechaHoy();
  cargarEmpleados();
  cargarSelectsEmpleados();
  cargarVacaciones();
  cargarNotificaciones();
});

// ── TABS ─────────────────────────────────────────────────────
function showTab(id) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + id).classList.add('active');
  event.target.classList.add('active');
}

// ── UTIL: FECHAS ─────────────────────────────────────────────
function setFechaHoy() {
  const hoy = new Date().toISOString().split('T')[0];
  const fi = document.getElementById('reg-fecha');
  const vi = document.getElementById('vac-inicio');
  const vf = document.getElementById('vac-fin');
  if (fi) fi.value = hoy;
  if (vi) vi.min = hoy;
  if (vf) vf.min = hoy;
}

// ── TOGGLE HORAS ─────────────────────────────────────────────
function toggleHoras() {
  const tipo  = document.getElementById('reg-tipo').value;
  const grp   = document.getElementById('grp-horas');
  const lbl   = document.getElementById('lbl-salario');
  const horas = document.getElementById('reg-horas');

  if (tipo === 'tiempo_parcial') {
    grp.style.display = 'flex';
    horas.value = 20;
    lbl.textContent = 'Salario base proporcional ($)';
  } else if (tipo === 'contratista') {
    grp.style.display = 'flex';
    horas.value = 40;
    lbl.textContent = 'Tarifa por hora ($)';
  } else {
    grp.style.display = 'none';
    horas.value = 40;
    lbl.textContent = 'Salario base mensual ($)';
  }
}
// Ejecutar al cargar para estado inicial correcto
document.addEventListener('DOMContentLoaded', toggleHoras);

// ── API HELPER ───────────────────────────────────────────────
async function api(params, method = 'GET') {
  const isPost = method === 'POST';
  const url    = isPost ? API_URL : `${API_URL}?${new URLSearchParams(params)}`;
  const opts   = isPost
    ? { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(params) }
    : { method: 'GET' };

  const res  = await fetch(url, opts);
  const json = await res.json();

  if (!json.success) throw new Error(json.error || 'Error desconocido');
  return json.data;
}

// ── TOAST ─────────────────────────────────────────────────────
let toastTimer;
function toast(msg, tipo = 'success') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = `show ${tipo}`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { el.className = ''; }, 3500);
}

// ── REGISTRAR EMPLEADO ────────────────────────────────────────
async function registrarEmpleado() {
  const nombre  = document.getElementById('reg-nombre').value.trim();
  const tipo    = document.getElementById('reg-tipo').value;
  const cargo   = document.getElementById('reg-cargo').value.trim();
  const salario = document.getElementById('reg-salario').value;
  const horas   = document.getElementById('reg-horas').value;
  const fecha   = document.getElementById('reg-fecha').value;
  const email   = document.getElementById('reg-email').value.trim();

  if (!nombre || !cargo || !salario || !fecha) {
    toast('⚠️ Completa todos los campos obligatorios', 'error');
    return;
  }

  try {
    await api({ accion: 'registrar', nombre, tipo, cargo,
                salario_base: salario, horas_semana: horas,
                fecha_ingreso: fecha, email }, 'POST');

    toast(`✅ Empleado "${nombre}" registrado correctamente`);
    // Limpiar form
    ['reg-nombre','reg-cargo','reg-salario','reg-email'].forEach(id => {
      document.getElementById(id).value = '';
    });
    cargarSelectsEmpleados();
    cargarEmpleados();
  } catch (e) {
    toast('❌ ' + e.message, 'error');
  }
}

// ── CARGAR LISTA DE EMPLEADOS ─────────────────────────────────
async function cargarEmpleados() {
  const tbody = document.getElementById('tabla-empleados');
  tbody.innerHTML = '<tr><td colspan="7" class="loading"><span class="spinner"></span>Cargando...</td></tr>';

  try {
    const empleados = await api({ accion: 'listar' });

    if (!empleados.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="empty"><span class="icon">👥</span>No hay empleados registrados</td></tr>';
      return;
    }

    tbody.innerHTML = empleados.map(e => {
      const t = TIPO_LABELS[e.tipo] || { label: e.tipo, cls: '' };
      return `<tr>
        <td>${e.id}</td>
        <td><strong>${e.nombre}</strong><br><small style="color:var(--gray-400)">${e.email}</small></td>
        <td><span class="pill ${t.cls}">${t.label}</span></td>
        <td>${e.cargo}</td>
        <td>$${parseFloat(e.salario_base).toLocaleString('es', {minimumFractionDigits:2})}</td>
        <td>${e.horas_semana}h</td>
        <td>${e.fecha_ingreso}</td>
      </tr>`;
    }).join('');
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="7" style="color:var(--danger);padding:20px">${e.message}</td></tr>`;
  }
}

// ── CARGAR SELECTS DE EMPLEADOS ───────────────────────────────
async function cargarSelectsEmpleados() {
  try {
    const empleados = await api({ accion: 'listar' });
    const opts = empleados.map(e =>
      `<option value="${e.id}">${e.nombre} (${TIPO_LABELS[e.tipo]?.label || e.tipo})</option>`
    ).join('');

    ['sal-empleado', 'vac-empleado'].forEach(id => {
      const sel = document.getElementById(id);
      if (sel) sel.innerHTML = `<option value="">-- Selecciona --</option>` + opts;
    });
  } catch (e) {
    console.error('Error cargando selects:', e);
  }
}

// ── CALCULAR SALARIO ──────────────────────────────────────────
async function calcularSalario() {
  const id = document.getElementById('sal-empleado').value;
  if (!id) { toast('⚠️ Selecciona un empleado', 'error'); return; }

  const div = document.getElementById('resultado-salario');
  div.innerHTML = '<div class="loading"><span class="spinner"></span>Calculando...</div>';

  try {
    const d = await api({ accion: 'calcular_salario', id });

    const filas = Object.entries({
      'Empleado':           d.empleado,
      'Tipo':               d.tipo_calculo,
      'Salario base':       d.salario_base   ? `$${parseFloat(d.salario_base).toLocaleString('es',{minimumFractionDigits:2})}` : '—',
      'Prestaciones':       d.prestaciones   ? `$${parseFloat(d.prestaciones).toLocaleString('es',{minimumFractionDigits:2})}` : '—',
      'Tarifa por hora':    d.tarifa_hora    ? `$${parseFloat(d.tarifa_hora).toFixed(2)}/hr` : null,
      'Horas al mes':       d.horas_mes      ? `${d.horas_mes}h` : null,
      'Detalle de cálculo': d.detalle        || '—',
      'Días de vacaciones': `${d.dias_vacaciones} días`,
      '💵 SALARIO FINAL':  `$${parseFloat(d.salario_final).toLocaleString('es',{minimumFractionDigits:2})}`,
    }).filter(([,v]) => v !== null);

    div.innerHTML = `<div class="salario-result">
      <h3>💵 Desglose de Salario</h3>
      ${filas.map(([k,v]) => `<div class="salario-row"><span>${k}</span><strong>${v}</strong></div>`).join('')}
    </div>`;
  } catch (e) {
    div.innerHTML = `<p style="color:var(--danger);padding:16px">❌ ${e.message}</p>`;
  }
}

// ── SOLICITAR VACACIONES ──────────────────────────────────────
async function solicitarVacaciones() {
  const empleadoId  = document.getElementById('vac-empleado').value;
  const fechaInicio = document.getElementById('vac-inicio').value;
  const fechaFin    = document.getElementById('vac-fin').value;

  if (!empleadoId || !fechaInicio || !fechaFin) {
    toast('⚠️ Completa todos los campos', 'error');
    return;
  }

  try {
    const d = await api({ accion: 'solicitar_vacaciones', empleado_id: empleadoId,
                          fecha_inicio: fechaInicio, fecha_fin: fechaFin }, 'POST');
    toast(`✅ Vacaciones solicitadas: ${d.dias} días (máx. ${d.dias_permitidos})`);
    cargarVacaciones();
    cargarNotificaciones();
  } catch (e) {
    toast('❌ ' + e.message, 'error');
  }
}

// ── CARGAR VACACIONES ─────────────────────────────────────────
async function cargarVacaciones() {
  const tbody = document.getElementById('tabla-vacaciones');
  tbody.innerHTML = '<tr><td colspan="7" class="loading"><span class="spinner"></span>Cargando...</td></tr>';

  try {
    const vacs = await api({ accion: 'listar_vacaciones' });

    if (!vacs.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="empty"><span class="icon">🏖️</span>Sin solicitudes de vacaciones</td></tr>';
      return;
    }

    tbody.innerHTML = vacs.map(v => {
      const t = TIPO_LABELS[v.empleado_tipo]  || { label: v.empleado_tipo, cls: '' };
      const e = ESTADO_LABELS[v.estado]        || { label: v.estado, cls: '' };
      const acciones = v.estado === 'pendiente'
        ? `<button class="btn btn-success btn-sm" onclick="aprobarVacacion(${v.id},'aprobada')">✔ Aprobar</button>
           <button class="btn btn-danger  btn-sm" onclick="aprobarVacacion(${v.id},'rechazada')" style="margin-left:4px">✖ Rechazar</button>`
        : `<span class="pill ${e.cls}">${e.label}</span>`;

      return `<tr>
        <td><strong>${v.empleado_nombre}</strong></td>
        <td><span class="pill ${t.cls}">${t.label}</span></td>
        <td>${v.fecha_inicio}</td>
        <td>${v.fecha_fin}</td>
        <td><strong>${v.dias}</strong></td>
        <td><span class="pill ${e.cls}">${e.label}</span></td>
        <td>${acciones}</td>
      </tr>`;
    }).join('');
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="7" style="color:var(--danger);padding:20px">${e.message}</td></tr>`;
  }
}

// ── APROBAR / RECHAZAR VACACIÓN ───────────────────────────────
async function aprobarVacacion(id, estado) {
  try {
    await api({ accion: 'aprobar_vacaciones', id, estado }, 'POST');
    toast(estado === 'aprobada' ? '✅ Vacaciones aprobadas' : '❌ Vacaciones rechazadas',
          estado === 'aprobada' ? 'success' : 'error');
    cargarVacaciones();
    cargarNotificaciones();
  } catch (e) {
    toast('❌ ' + e.message, 'error');
  }
}

// ── GENERAR REPORTE ───────────────────────────────────────────
async function generarReporte() {
  const tbody = document.getElementById('tabla-reporte');
  tbody.innerHTML = '<tr><td colspan="6" class="loading"><span class="spinner"></span>Generando reporte...</td></tr>';

  try {
    const r = await api({ accion: 'reporte' });

    // Stats cards
    document.getElementById('st-total').textContent  = r.total_empleados;
    document.getElementById('st-nomina').textContent  = `$${parseFloat(r.total_nomina).toLocaleString('es',{minimumFractionDigits:2})}`;
    document.getElementById('st-tc').textContent      = r.por_tipo.tiempo_completo  || 0;
    document.getElementById('st-tp').textContent      = r.por_tipo.tiempo_parcial   || 0;

    if (!r.detalle.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="empty"><span class="icon">📊</span>Sin datos</td></tr>';
      return;
    }

    tbody.innerHTML = r.detalle.map(emp => {
      const t = TIPO_LABELS[emp.tipo] || { label: emp.tipo, cls: '' };
      return `<tr>
        <td><strong>${emp.nombre}</strong></td>
        <td><span class="pill ${t.cls}">${t.label}</span></td>
        <td>${emp.cargo}</td>
        <td><small style="color:var(--gray-600)">${emp.tipo_calculo}</small></td>
        <td><strong>$${parseFloat(emp.salario_final).toLocaleString('es',{minimumFractionDigits:2})}</strong></td>
        <td>${emp.vacaciones_tomadas} solicitudes</td>
      </tr>`;
    }).join('');

    toast('📊 Reporte generado correctamente');
    cargarNotificaciones();
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="6" style="color:var(--danger);padding:20px">${e.message}</td></tr>`;
  }
}

// ── NOTIFICACIONES ────────────────────────────────────────────
async function cargarNotificaciones() {
  const div = document.getElementById('noti-list');
  div.innerHTML = '<div class="loading"><span class="spinner"></span>Cargando...</div>';

  try {
    const notis = await api({ accion: 'notificaciones' });

    if (!notis.length) {
      div.innerHTML = '<div class="empty"><span class="icon">🔔</span>No hay notificaciones</div>';
      return;
    }

    const iconos = { registro:'✅', vacaciones:'🏖️', reporte:'📊', info:'ℹ️' };

    div.innerHTML = notis.map(n => {
      const icono = iconos[n.tipo] || 'ℹ️';
      const fecha = new Date(n.created_at).toLocaleString('es');
      return `<div class="noti-item ${n.leida == '0' ? 'unread' : ''}" id="noti-${n.id}">
        <span class="icon">${icono}</span>
        <div class="body">
          <div class="msg">${n.mensaje}</div>
          <div class="time">${fecha}${n.empleado_nombre ? ' · ' + n.empleado_nombre : ''}</div>
        </div>
        ${n.leida == '0'
          ? `<button class="btn btn-outline btn-sm" onclick="marcarLeida(${n.id})">Marcar leída</button>`
          : '<span style="color:var(--gray-400);font-size:0.8rem">✔ Leída</span>'}
      </div>`;
    }).join('');
  } catch (e) {
    div.innerHTML = `<p style="color:var(--danger);padding:20px">❌ ${e.message}</p>`;
  }
}

async function marcarLeida(id) {
  try {
    await api({ accion: 'marcar_leida', id }, 'POST');
    const el = document.getElementById(`noti-${id}`);
    if (el) el.classList.remove('unread');
    cargarNotificaciones();
  } catch (e) {
    toast('❌ ' + e.message, 'error');
  }
}
