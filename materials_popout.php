<?php
require_once 'includes/auth.php';
Auth::requireAuth();
require_once 'includes/models/Material.php';
require_once 'includes/models/Supplier.php';
require_once 'includes/models/MiscMaterial.php';
require_once 'includes/models/FoamGrade.php';
require_once 'includes/models/Settings.php';

$materialModel = new Material();
$supplierModel = new Supplier();
$miscModel = new MiscMaterial();
$foamGradeModel = new FoamGrade();
$settingsModel = new Settings();

$lowStockThreshold = (float)$settingsModel->get('materials_low_stock_threshold', '0');

$currentTheme = $settingsModel->get('user_theme_' . Auth::getCurrentUserId(), 'light');
if (!in_array($currentTheme, ['light', 'dark'], true)) {
    $currentTheme = 'light';
}

$materials = array_map(function ($m) {
    return [
        'id' => (int)$m['id'],
        'name' => $m['item_name'],
        'code' => $m['manufacturers_code'],
        'supplier_id' => $m['supplier_id'] ? (int)$m['supplier_id'] : null,
        'supplier' => $m['supplier_name'],
        'stock' => (float)$m['stock_on_hand'],
        'unit' => $m['unit_of_measure'],
        'sell' => (float)$m['sell_price'],
    ];
}, $materialModel->getAll(['active_only' => 1]));

$suppliers = array_map(function ($s) {
    return ['id' => (int)$s['id'], 'name' => $s['name']];
}, $supplierModel->getActive());

$foam = array_map(function ($g) {
    return [
        'grade' => $g['grade_code'],
        'description' => $g['description'],
        'products' => array_map(function ($p) {
            return [
                'thickness' => $p['thickness'],
                'sheet_cost' => (float)$p['sheet_cost'],
                'sheet_area' => (float)$p['sheet_area'],
            ];
        }, array_values(array_filter($g['products'], fn($p) => $p['is_active']))),
    ];
}, $foamGradeModel->getWithProducts(true));

$misc = array_map(function ($m) {
    return ['name' => $m['name'], 'price' => (float)$m['fixed_price']];
}, $miscModel->getActive());

$payload = [
    'materials' => $materials,
    'suppliers' => $suppliers,
    'foam' => $foam,
    'misc' => $misc,
    'threshold' => $lowStockThreshold,
];

// JSON mode used by the refresh button
if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $currentTheme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock - WorkTrack</title>
    <link rel="icon" type="image/svg+xml" href="public/images/favicon.svg">
    <link rel="stylesheet" href="public/css/theme.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* Local aliases onto the shared theme variables (theme.css) */
        :root, [data-theme="dark"] {
            --panel: var(--surface);
            --panel-2: var(--surface-2);
            --panel-3: var(--surface-3);
            --muted: var(--text-muted);
            --ok: var(--success-text);
            --warn: var(--warning-text);
            --bad: var(--danger-text);
        }
        html, body { height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            overflow: hidden;
        }
        .app { display: flex; flex-direction: column; height: 100vh; }

        /* Header */
        .top {
            padding: 12px 14px 0;
            background: var(--panel);
            border-bottom: 1px solid var(--line);
        }
        .top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 15px;
        }
        .brand-dot {
            width: 9px; height: 9px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 8px var(--accent);
        }
        .top-meta { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 12px; }
        .icon-btn {
            background: var(--panel-2);
            border: 1px solid var(--line);
            color: var(--text);
            border-radius: 7px;
            width: 28px; height: 28px;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
            transition: background 0.15s;
        }
        .icon-btn:hover { background: var(--panel-3); }
        .icon-btn.spinning { animation: spin 0.7s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .tabs { display: flex; gap: 2px; }
        .tab {
            flex: 1;
            background: none;
            border: none;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            padding: 9px 4px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        .tab.active { color: var(--text); border-bottom-color: var(--accent); }
        .tab:hover { color: var(--text); }

        /* Stock status chips */
        .chips {
            display: flex;
            gap: 6px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--line);
            background: var(--bg);
        }
        .chip {
            background: var(--panel);
            border: 1px solid var(--line);
            color: var(--muted);
            border-radius: 999px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .chip.active { background: var(--accent); border-color: var(--accent); color: var(--accent-contrast); }

        /* List */
        .list { flex: 1; overflow-y: auto; overscroll-behavior: contain; }
        .list::-webkit-scrollbar { width: 8px; }
        .list::-webkit-scrollbar-thumb { background: var(--line); border-radius: 4px; }
        .count-line { padding: 8px 14px 2px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .row { border-bottom: 1px solid var(--line); }
        .row-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 14px;
        }
        .row-info { min-width: 0; }
        .row-name { font-weight: 600; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .row-meta { color: var(--muted); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .row-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .row-price { color: var(--muted); font-size: 12px; min-width: 52px; text-align: right; }

        .stock-pill {
            border: 1px solid var(--line);
            border-radius: 7px;
            padding: 4px 9px;
            font-weight: 700;
            font-size: 13px;
            min-width: 58px;
            text-align: center;
            cursor: pointer;
            background: var(--panel);
            transition: border-color 0.15s;
        }
        .stock-pill:hover { border-color: var(--accent); }
        .stock-pill .unit { font-weight: 400; font-size: 10px; color: var(--muted); display: block; line-height: 1.1; }
        .stock-ok { color: var(--ok); }
        .stock-low { color: var(--warn); }
        .stock-out { color: var(--bad); }
        .stock-pill.flash { animation: flash 0.8s; }
        @keyframes flash { 0% { background: rgba(52,199,123,0.35); } 100% { background: var(--panel); } }

        /* Inline stock editor */
        .stock-editor {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 0 14px 10px;
        }
        .stock-editor .step {
            background: var(--panel-2);
            border: 1px solid var(--line);
            color: var(--text);
            width: 30px; height: 30px;
            border-radius: 7px;
            font-size: 16px;
            cursor: pointer;
        }
        .stock-editor .step:hover { border-color: var(--accent); }
        .stock-editor input {
            background: var(--panel);
            border: 1px solid var(--accent);
            color: var(--text);
            border-radius: 7px;
            width: 80px;
            height: 30px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
        }
        .stock-editor input:focus { outline: none; }
        .stock-editor .save {
            background: var(--accent);
            border: none;
            color: var(--accent-contrast);
            font-weight: 600;
            height: 30px;
            padding: 0 14px;
            border-radius: 7px;
            cursor: pointer;
        }
        .stock-editor .cancel {
            background: none;
            border: none;
            color: var(--muted);
            height: 30px;
            padding: 0 8px;
            cursor: pointer;
            font-size: 14px;
        }
        .stock-editor .cancel:hover { color: var(--bad); }
        .editor-note { color: var(--muted); font-size: 11px; padding: 0 14px 10px; }

        /* Foam + misc */
        .group-header {
            padding: 10px 14px 4px;
            font-size: 12px;
            font-weight: 700;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .group-sub { color: var(--muted); font-weight: 400; text-transform: none; letter-spacing: 0; }
        .foam-row {
            display: flex;
            justify-content: space-between;
            padding: 7px 14px;
            border-bottom: 1px solid var(--line);
            font-size: 13px;
        }
        .foam-cost { color: var(--muted); }
        .foam-cost strong { color: var(--text); }

        .empty { text-align: center; color: var(--muted); padding: 40px 20px; }

        /* Slide-up panels */
        .panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.22s ease;
            background: var(--panel);
            border-top: 1px solid transparent;
        }
        .panel.open { max-height: 64px; border-top-color: var(--line); }
        .panel-inner { display: flex; align-items: center; gap: 8px; padding: 10px 14px; }
        .panel-icon { font-size: 13px; opacity: 0.7; flex-shrink: 0; }
        .panel input[type=text], .panel select {
            flex: 1;
            background: var(--panel-2);
            border: 1px solid var(--line);
            color: var(--text);
            border-radius: 8px;
            height: 34px;
            padding: 0 12px;
            font-size: 14px;
            min-width: 0;
        }
        .panel input[type=text]:focus, .panel select:focus { outline: none; border-color: var(--accent); }
        .panel .clear-x {
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 14px;
            padding: 4px;
            flex-shrink: 0;
        }
        .panel .clear-x:hover { color: var(--bad); }

        /* Footer */
        .footer {
            display: flex;
            border-top: 1px solid var(--line);
            background: var(--panel);
        }
        .footer-btn {
            flex: 1;
            background: none;
            border: none;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            padding: 13px 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: color 0.15s, background 0.15s;
        }
        .footer-btn + .footer-btn { border-left: 1px solid var(--line); }
        .footer-btn:hover:not(:disabled) { color: var(--text); background: var(--panel-2); }
        .footer-btn.active { color: var(--accent); background: var(--panel-2); box-shadow: inset 0 2px 0 var(--accent); }
        .footer-btn:disabled { opacity: 0.35; cursor: default; }
        .footer-btn .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--accent);
            display: none;
        }
        .footer-btn.filtered .dot { display: inline-block; }
    </style>
</head>
<body>
<div class="app">
    <div class="top">
        <div class="top-row">
            <div class="brand"><span class="brand-dot"></span> Stock</div>
            <div class="top-meta">
                <span id="updatedAt"></span>
                <button type="button" class="icon-btn" id="themeToggle" title="Toggle light/dark mode"><?php echo $currentTheme === 'dark' ? '&#9728;' : '&#127769;'; ?></button>
                <button type="button" class="icon-btn" id="refreshBtn" title="Refresh stock data">&#8635;</button>
            </div>
        </div>
        <div class="tabs">
            <button type="button" class="tab active" data-tab="materials">Materials</button>
            <button type="button" class="tab" data-tab="foam">Foam Products</button>
            <button type="button" class="tab" data-tab="misc">Miscellaneous</button>
        </div>
    </div>

    <div class="chips" id="stockChips">
        <button type="button" class="chip active" data-stock="">All</button>
        <button type="button" class="chip" data-stock="in">In Stock</button>
        <button type="button" class="chip" data-stock="low">Low</button>
        <button type="button" class="chip" data-stock="out">Out</button>
    </div>

    <div class="list" id="list"></div>

    <div class="panel" id="searchPanel">
        <div class="panel-inner">
            <span class="panel-icon">&#128269;</span>
            <input type="text" id="searchInput" placeholder="Search by name, code or supplier...">
            <button type="button" class="clear-x" id="searchClear" title="Clear search">&#10005;</button>
        </div>
    </div>
    <div class="panel" id="supplierPanel">
        <div class="panel-inner">
            <span class="panel-icon">&#127991;</span>
            <select id="supplierSelect">
                <option value="">All Suppliers</option>
            </select>
            <button type="button" class="clear-x" id="supplierClear" title="Clear supplier filter">&#10005;</button>
        </div>
    </div>

    <div class="footer">
        <button type="button" class="footer-btn" id="searchBtn">&#128269; Search <span class="dot"></span></button>
        <button type="button" class="footer-btn" id="supplierBtn">&#127991; Suppliers <span class="dot"></span></button>
    </div>
</div>

<script>
let DATA = <?php echo json_encode($payload); ?>;

const state = {
    tab: 'materials',
    search: '',
    supplierId: '',
    stock: '',
    editingId: null
};

const list = document.getElementById('list');
const searchPanel = document.getElementById('searchPanel');
const supplierPanel = document.getElementById('supplierPanel');
const searchBtn = document.getElementById('searchBtn');
const supplierBtn = document.getElementById('supplierBtn');
const searchInput = document.getElementById('searchInput');
const supplierSelect = document.getElementById('supplierSelect');
const chipsBar = document.getElementById('stockChips');

function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function money(v) {
    return '$' + Number(v).toFixed(2);
}

function fmtQty(v) {
    return Number(v) % 1 === 0 ? String(Number(v)) : Number(v).toFixed(2);
}

function stockClass(m) {
    if (m.stock <= 0) return 'stock-out';
    if (m.stock <= DATA.threshold) return 'stock-low';
    return 'stock-ok';
}

function setUpdatedNow() {
    document.getElementById('updatedAt').textContent =
        'Updated ' + new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
}

function populateSuppliers() {
    const current = state.supplierId;
    supplierSelect.innerHTML = '<option value="">All Suppliers</option>' +
        DATA.suppliers.map(s => `<option value="${s.id}">${esc(s.name)}</option>`).join('');
    supplierSelect.value = current;
}

function filteredMaterials() {
    const q = state.search.toLowerCase();
    return DATA.materials.filter(m => {
        if (state.supplierId && String(m.supplier_id) !== state.supplierId) return false;
        if (state.stock === 'in' && m.stock <= 0) return false;
        if (state.stock === 'low' && m.stock > DATA.threshold) return false;
        if (state.stock === 'out' && m.stock > 0) return false;
        if (q && !((m.name || '').toLowerCase().includes(q) ||
                   (m.code || '').toLowerCase().includes(q) ||
                   (m.supplier || '').toLowerCase().includes(q))) return false;
        return true;
    });
}

function render() {
    if (state.tab === 'materials') renderMaterials();
    else if (state.tab === 'foam') renderFoam();
    else renderMisc();
    updateFooterState();
}

function renderMaterials() {
    const items = filteredMaterials();
    if (!items.length) {
        list.innerHTML = '<div class="empty">No materials match the current filters.</div>';
        return;
    }
    let html = `<div class="count-line">${items.length} item${items.length === 1 ? '' : 's'}</div>`;
    for (const m of items) {
        const editing = state.editingId === m.id;
        html += `<div class="row" data-id="${m.id}">
            <div class="row-main">
                <div class="row-info">
                    <div class="row-name" title="${esc(m.name)}">${esc(m.name)}</div>
                    <div class="row-meta">${esc(m.code || '-')}${m.supplier ? ' &middot; ' + esc(m.supplier) : ''}</div>
                </div>
                <div class="row-right">
                    <div class="row-price">${money(m.sell)}</div>
                    <button type="button" class="stock-pill ${stockClass(m)}" data-edit="${m.id}" title="Click to edit stock level">
                        ${fmtQty(m.stock)}<span class="unit">${esc(m.unit || 'each')}</span>
                    </button>
                </div>
            </div>`;
        if (editing) {
            html += `<div class="stock-editor">
                    <button type="button" class="step" data-step="-1">&minus;</button>
                    <input type="number" step="0.01" id="stockEditInput" value="${fmtQty(m.stock)}">
                    <button type="button" class="step" data-step="1">+</button>
                    <button type="button" class="save" data-save="${m.id}">Save</button>
                    <button type="button" class="cancel" data-cancel="1">&#10005;</button>
                </div>
                <div class="editor-note">Sets the new stock on hand. Change is logged as a manual adjustment.</div>`;
        }
        html += '</div>';
    }
    list.innerHTML = html;
    if (state.editingId !== null) {
        const input = document.getElementById('stockEditInput');
        if (input) { input.focus(); input.select(); }
    }
}

function renderFoam() {
    const q = state.search.toLowerCase();
    let html = '';
    for (const g of DATA.foam) {
        const gradeMatch = (g.grade + ' ' + (g.description || '')).toLowerCase().includes(q);
        const products = q && !gradeMatch
            ? g.products.filter(p => String(p.thickness).toLowerCase().includes(q))
            : g.products;
        if (q && !gradeMatch && !products.length) continue;
        html += `<div class="group-header">${esc(g.grade)}${g.description ? ' <span class="group-sub">&middot; ' + esc(g.description) + '</span>' : ''}</div>`;
        if (!products.length) {
            html += '<div class="foam-row"><span class="foam-cost">No products</span></div>';
            continue;
        }
        for (const p of products) {
            const perM2 = p.sheet_area > 0 ? p.sheet_cost / p.sheet_area : 0;
            html += `<div class="foam-row">
                <span><strong>${esc(p.thickness)}</strong></span>
                <span class="foam-cost">${money(p.sheet_cost)}/sheet &middot; <strong>${money(perM2)}</strong>/m&sup2;</span>
            </div>`;
        }
    }
    list.innerHTML = html || '<div class="empty">No foam products match the current search.</div>';
}

function renderMisc() {
    const q = state.search.toLowerCase();
    const items = DATA.misc.filter(m => !q || m.name.toLowerCase().includes(q));
    if (!items.length) {
        list.innerHTML = '<div class="empty">No miscellaneous charges match the current search.</div>';
        return;
    }
    list.innerHTML = `<div class="count-line">${items.length} item${items.length === 1 ? '' : 's'}</div>` +
        items.map(m => `<div class="foam-row">
            <span><strong>${esc(m.name)}</strong></span>
            <span class="foam-cost"><strong>${money(m.price)}</strong></span>
        </div>`).join('');
}

function updateFooterState() {
    searchBtn.classList.toggle('filtered', state.search !== '');
    supplierBtn.classList.toggle('filtered', state.supplierId !== '');
    const materialsTab = state.tab === 'materials';
    supplierBtn.disabled = !materialsTab;
    if (!materialsTab) supplierPanel.classList.remove('open');
    supplierBtn.classList.toggle('active', materialsTab && supplierPanel.classList.contains('open'));
    searchBtn.classList.toggle('active', searchPanel.classList.contains('open'));
    chipsBar.style.display = materialsTab ? 'flex' : 'none';
}

// Tabs
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        state.tab = tab.dataset.tab;
        state.editingId = null;
        render();
    });
});

// Stock status chips
chipsBar.addEventListener('click', e => {
    const chip = e.target.closest('.chip');
    if (!chip) return;
    chipsBar.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    state.stock = chip.dataset.stock;
    state.editingId = null;
    render();
});

// Footer toggles
searchBtn.addEventListener('click', () => {
    searchPanel.classList.toggle('open');
    updateFooterState();
    if (searchPanel.classList.contains('open')) searchInput.focus();
});
supplierBtn.addEventListener('click', () => {
    supplierPanel.classList.toggle('open');
    updateFooterState();
});

// Search + supplier controls
searchInput.addEventListener('input', () => {
    state.search = searchInput.value.trim();
    state.editingId = null;
    render();
});
document.getElementById('searchClear').addEventListener('click', () => {
    searchInput.value = '';
    state.search = '';
    render();
    searchInput.focus();
});
supplierSelect.addEventListener('change', () => {
    state.supplierId = supplierSelect.value;
    state.editingId = null;
    render();
});
document.getElementById('supplierClear').addEventListener('click', () => {
    supplierSelect.value = '';
    state.supplierId = '';
    render();
});

// Inline stock editing
list.addEventListener('click', e => {
    const pill = e.target.closest('[data-edit]');
    if (pill) {
        const id = Number(pill.dataset.edit);
        state.editingId = state.editingId === id ? null : id;
        render();
        return;
    }
    const step = e.target.closest('[data-step]');
    if (step) {
        const input = document.getElementById('stockEditInput');
        const next = (parseFloat(input.value) || 0) + Number(step.dataset.step);
        input.value = fmtQty(Math.max(0, next));
        return;
    }
    if (e.target.closest('[data-cancel]')) {
        state.editingId = null;
        render();
        return;
    }
    const save = e.target.closest('[data-save]');
    if (save) saveStock(Number(save.dataset.save));
});

list.addEventListener('keydown', e => {
    if (e.target.id === 'stockEditInput') {
        if (e.key === 'Enter' && state.editingId !== null) saveStock(state.editingId);
        if (e.key === 'Escape') { state.editingId = null; render(); }
    }
});

async function saveStock(id) {
    const input = document.getElementById('stockEditInput');
    const newStock = parseFloat(input.value);
    if (isNaN(newStock) || newStock < 0) {
        input.style.borderColor = 'var(--bad)';
        return;
    }
    const saveBtn = list.querySelector('[data-save]');
    if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = '...'; }
    try {
        const res = await fetch('api/materialStockAdjust.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id, new_stock: newStock, notes: 'Stock window update'})
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Update failed');
        const mat = DATA.materials.find(m => m.id === id);
        if (mat) mat.stock = data.stock_on_hand;
        state.editingId = null;
        render();
        const updatedPill = list.querySelector(`[data-edit="${id}"]`);
        if (updatedPill) updatedPill.classList.add('flash');
    } catch (err) {
        alert('Could not update stock: ' + err.message);
        if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; }
    }
}

// Refresh
document.getElementById('refreshBtn').addEventListener('click', async function() {
    this.classList.add('spinning');
    try {
        const res = await fetch('materials_popout.php?format=json');
        if (res.ok) {
            DATA = await res.json();
            state.editingId = null;
            populateSuppliers();
            render();
            setUpdatedNow();
        }
    } finally {
        this.classList.remove('spinning');
    }
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && state.editingId === null) {
        searchPanel.classList.remove('open');
        supplierPanel.classList.remove('open');
        updateFooterState();
    }
});

// Theme toggle, kept in sync with the main window via localStorage
const themeToggle = document.getElementById('themeToggle');
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    themeToggle.innerHTML = theme === 'dark' ? '&#9728;' : '&#127769;';
    try { localStorage.setItem('wt-theme', theme); } catch (e) {}
}
themeToggle.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    fetch('api/userTheme.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({theme: next})
    });
});
window.addEventListener('storage', e => {
    if (e.key === 'wt-theme' && (e.newValue === 'light' || e.newValue === 'dark')) applyTheme(e.newValue);
});

populateSuppliers();
setUpdatedNow();
render();
</script>
</body>
</html>
