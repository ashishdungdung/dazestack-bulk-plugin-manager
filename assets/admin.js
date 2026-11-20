(function () {
	'use strict';

	if (typeof DSBPM_DATA === 'undefined') {
		return;
	}

	const state = {
		queue: [],
		health: {},
		deps: {},
		vuln: {},
		payload: { total: 0, sizes: {} },
		metrics: null,
		managed: [],
		partnerOffers: [],
		partnerSource: '',
		queueIndex: 0,
		installing: false
	};

	const el = (id) => document.getElementById(id);
	const __ = (text) => (window.wp && window.wp.i18n ? window.wp.i18n.__(text, 'dazestack-bulk-plugin-manager') : text);
	const queueEl = el('dsbpm-queue');
	const depsEl = el('dsbpm-deps');
	const queueSummaryEl = el('dsbpm-queue-summary');
	const queueEmptyEl = el('dsbpm-queue-empty');
	const toastEl = el('dsbpm-toast');
	const importMetaEl = el('dsbpm-import-meta');
	const remoteStatusEl = el('dsbpm-remote-status');
	const healthReportEl = el('dsbpm-health-report');
	const payloadMetaEl = el('dsbpm-payload-meta');
	const metricsChartEl = el('dsbpm-metrics-chart');
	const autoPreflightEl = el('dsbpm-auto-preflight');
	const autoPayloadEl = el('dsbpm-auto-payload');
	const promoHeroEl = el('dsbpm-promo-hero');
	const promoGridEl = el('dsbpm-promo-grid');
	const promoSourceEl = el('dsbpm-promo-source');
	const stopInstallBtn = el('dsbpm-stop-install');
	const startInstallBtn = el('dsbpm-start-install');

	let toastTimer = null;
	let stopRequested = false;

	function debounce(fn, delay = 400) {
		let timer;
		return (...args) => {
			clearTimeout(timer);
			timer = setTimeout(() => fn.apply(null, args), delay);
		};
	}

	function encodeData(payload) {
		const data = new FormData();
		data.append('nonce', DSBPM_DATA.nonce);
		for (const key in payload) {
			if (!Object.prototype.hasOwnProperty.call(payload, key)) {
				continue;
			}
			if (key === 'action') {
				continue;
			}
			const value = payload[key];
			if (typeof value === 'object') {
				data.append(key, JSON.stringify(value));
			} else if (typeof value !== 'undefined') {
				data.append(key, value);
			}
		}
		return data;
	}

	async function ajax(action, payload) {
		const data = encodeData(payload || {});
		data.append('action', action);
		try {
			const res = await fetch(DSBPM_DATA.ajaxUrl, {
				method: 'POST',
				body: data,
				credentials: 'same-origin'
			});
			const json = await res.json();
			if (!res.ok && json && typeof json.success === 'boolean') {
				json.success = false;
			} else if (!res.ok && (!json || typeof json.success === 'undefined')) {
				return { success: false, data: { message: `Request failed (${res.status})` } };
			}
			return json;
		} catch (error) {
			return { success: false, data: { message: error.message || 'Network error' } };
		}
	}

	function escapeHtml(value) {
		const div = document.createElement('div');
		div.textContent = value;
		return div.innerHTML;
	}

	function sanitizeUrl(value) {
		if (!value) return '';
		try {
			const url = new URL(String(value), window.location.origin);
			if (['http:', 'https:'].includes(url.protocol)) {
				return url.href;
			}
		} catch (error) {
			return '';
		}
		return '';
	}

	function slugify(value) {
		return value
			.trim()
			.toLowerCase()
			.replace(/[\s_]+/g, '-')
			.replace(/[^a-z0-9-]/g, '')
			.replace(/-+/g, '-')
			.replace(/^-+|-+$/g, '');
	}

	function withTrackingUrl(value, context) {
		const href = sanitizeUrl(value);
		if (!href) return '';
		try {
			const url = new URL(href, window.location.origin);
			const host = url.hostname.replace(/^www\./, '');
			const trackDomains = ['dazestack.com', 'ashishdungdung.com'];
			const shouldTrack = trackDomains.some((domain) => host === domain || host.endsWith(`.${domain}`));
			if (!shouldTrack) {
				return url.href;
			}
			url.searchParams.set('utm_source', 'dazestack-bulk-plugin-manager');
			url.searchParams.set('utm_medium', 'plugin');
			url.searchParams.set('utm_campaign', 'wp-admin');
			if (context) {
				url.searchParams.set('utm_content', slugify(String(context)));
			}
			return url.toString();
		} catch (error) {
			return href;
		}
	}

	function normalizeBool(value) {
		if (typeof value === 'boolean') return value;
		if (typeof value === 'number') return value === 1;
		if (!value) return false;
		const str = String(value).trim().toLowerCase();
		return ['1', 'true', 'yes', 'y'].includes(str);
	}

	function normalizeItem(raw) {
		if (!raw) raw = {};
		if (typeof raw === 'string') {
			raw = { slug: raw };
		}
		if (!raw.slug && raw.name) {
			raw.slug = raw.name;
		}
		const item = {
			slug: raw.slug ? slugify(raw.slug) : '',
			source: raw.source || '',
			version: raw.version || '',
			activate: normalizeBool(raw.activate),
			network: normalizeBool(raw.network),
			force: normalizeBool(raw.force),
			status: raw.status || 'pending',
			warnings: raw.warnings || [],
			progress: raw.progress || 0
		};
		return item;
	}

	function toast(message, tone = 'info') {
		if (!toastEl || !message) {
			return;
		}
		toastEl.textContent = message;
		toastEl.className = `dsbpm-toast is-${tone}`;
		toastEl.classList.add('is-show');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(() => toastEl.classList.remove('is-show'), 3600);
	}

	function resolveError(res, fallback) {
		if (res && res.data && res.data.message) {
			return res.data.message;
		}
		return fallback || 'Something went wrong.';
	}

	function animateProgress(el, item, target) {
		if (!el) return;
		const current = typeof item._displayProgress === 'number' ? item._displayProgress : 0;
		if (current === target) {
			el.textContent = `${target}%`;
			return;
		}
		const start = current;
		const diff = target - start;
		const startTime = performance.now();
		const duration = 420;

		function step(now) {
			const progress = Math.min(1, (now - startTime) / duration);
			const value = Math.round(start + diff * progress);
			item._displayProgress = value;
			el.textContent = `${value}%`;
			if (progress < 1) {
				requestAnimationFrame(step);
			}
		}
		requestAnimationFrame(step);
	}

	function renderQueueState() {
		const total = state.queue.length;
		const activations = state.queue.filter((item) => item.activate).length;
		if (queueSummaryEl) {
			queueSummaryEl.textContent = total
				? `${total} plugin${total > 1 ? 's' : ''} queued - ${activations} set to auto-activate - Smart Sort keeps dependencies happy.`
				: 'Lean queue. Add plugins or load a favorite.';
		}
		if (queueEmptyEl) {
			queueEmptyEl.style.display = total ? 'none' : 'block';
		}
	}

	function badge(label, css) {
		return `<span class="dsbpm-chip ${css || ''}">${escapeHtml(String(label || ''))}</span>`;
	}

	function statusLabel(status) {
		const map = {
			installed: 'Installed',
			installing: 'Installing',
			queued: 'Queued',
			pending: 'Pending',
			warn: 'Warning',
			simulated: 'Simulated',
			rescued: 'Rescued',
			blocked: 'Blocked',
			skipped: 'Skipped',
			downgrade_blocked: 'Downgrade blocked',
			waiting_credentials: 'Needs credentials'
		};
		return map[status] || status || 'Pending';
	}

	function addItems(items) {
		const existing = new Set(state.queue.map((item) => item.slug || item.source));
		items.forEach((item) => {
			const key = item.slug || item.source;
			if (!key || existing.has(key)) return;
			state.queue.push(item);
			existing.add(key);
		});
		if (importMetaEl) {
			importMetaEl.textContent = `${state.queue.length} plugin${state.queue.length === 1 ? '' : 's'} staged.`;
		}
		renderQueue();
		if (autoPreflightEl && autoPreflightEl.checked) {
			runPreflightDebounced();
		}
		if (autoPayloadEl && autoPayloadEl.checked) {
			runPayloadDebounced();
		}
	}

	function renderQueue() {
		if (!queueEl) return;

		const existingCards = Array.from(queueEl.querySelectorAll('.dsbpm-queue-card'));
		const existingSlugs = new Set(existingCards.map(c => c.dataset.slug));
		const currentSlugs = new Set(state.queue.map(item => item.slug || 'custom-source'));

		// Remove cards that are no longer in the queue
		existingCards.forEach(card => {
			if (!currentSlugs.has(card.dataset.slug)) {
				card.remove();
			}
		});

		state.queue.forEach((item, index) => {
			const slug = item.slug || 'custom-source';
			let card = queueEl.querySelector(`.dsbpm-queue-card[data-slug="${slug}"]`);
			const isNew = !card;

			if (isNew) {
				card = document.createElement('div');
				card.className = 'dsbpm-queue-card';
				card.dataset.slug = slug;
				queueEl.appendChild(card);
			}

			// Update content only if needed (or just always update for now, but without innerHTML if possible)
			// For simplicity and to avoid complex diffing, we can use a template literal if the performance is acceptable
			// The key is NOT clearing the whole parent.

			const sizeText = item.size ? `${(item.size / 1024 / 1024).toFixed(1)}MB` : '--';
			const warnings = item.warnings && item.warnings.length ? item.warnings.join(' - ') : 'Clean - no risk flags';
			const statusTone = ['installed', 'queued'].includes(item.status)
				? 'is-good'
				: ['error', 'blocked', 'activation_failed', 'rescued'].includes(item.status)
					? 'is-bad'
					: (item.warnings && item.warnings.length) ? 'is-warn' : 'is-neutral';
			const sourceText = item.source ? 'Custom source' : 'wp.org';
			const badges = [
				badge(statusLabel(item.status), `dsbpm-badge ${statusTone}`),
				item.activate ? badge('Activate', 'dsbpm-chip--on') : '',
				item.network ? badge('Network', 'dsbpm-chip--ghost') : '',
				item.force ? badge('Force', 'dsbpm-chip--ghost') : '',
				item.vulnCritical ? badge('Blocked', 'dsbpm-chip--danger') : ''
			].join('');
			const progressValue = Math.min(100, Math.max(0, Math.round(item.progress || 0)));

			// Update card classes
			card.className = 'dsbpm-queue-card';
			if (item.status === 'error' || item.status === 'activation_failed' || item.status === 'rescued') {
				card.classList.add('dsbpm-queue-card--error');
			} else if (item.warnings && item.warnings.length) {
				card.classList.add('dsbpm-queue-card--warn');
			}
			if (item.vulnCritical) {
				card.classList.add('dsbpm-queue-card--blocked');
			}

			// Update inner content if it changed (optimization)
			const newHTML = `
				<div class="dsbpm-queue-card__top">
					<div class="dsbpm-queue-card__title">${escapeHtml(item.slug || 'custom-source')}</div>
					<div class="dsbpm-queue-card__actions">
						<button class="dsbpm-chip dsbpm-chip--ghost" data-action="move-up" aria-label="Move up">&uarr;</button>
						<button class="dsbpm-chip dsbpm-chip--ghost" data-action="move-down" aria-label="Move down">&darr;</button>
						<button class="dsbpm-chip dsbpm-chip--ghost" data-action="remove" aria-label="Remove">&times;</button>
					</div>
				</div>
				<div class="dsbpm-queue-card__meta">Version: ${escapeHtml(item.version || 'latest')} - Size: ${sizeText} - ${sourceText}</div>
				<div class="dsbpm-queue-card__badges">${badges}</div>
				<div class="dsbpm-queue-card__status">${escapeHtml(warnings)}</div>
				<div class="dsbpm-queue-card__progress" data-progress="${progressValue}">${progressValue}%</div>
				<div class="dsbpm-queue-card__bar" style="width:${item.progress || 0}%"></div>
			`;

			if (card.innerHTML !== newHTML) {
				card.innerHTML = newHTML;
			}

			card.dataset.index = String(index);

			animateProgress(card.querySelector('.dsbpm-queue-card__progress'), item, progressValue);
		});

		renderQueueState();
		renderDeps();
	}

	function renderHealth() {
		const ring = el('dsbpm-health-score');
		const meta = el('dsbpm-health-meta');
		const circle = document.querySelector('.dsbpm-health__value');
		const scores = Object.values(state.health)
			.map((h) => h.score)
			.filter((value) => typeof value === 'number');

		if (!scores.length) {
			if (ring) ring.textContent = '--';
			if (meta) meta.textContent = 'Run pre-flight to see risk flags.';
			if (circle) circle.style.strokeDashoffset = '339';
			return;
		}

		const avg = Math.round(scores.reduce((a, b) => a + b, 0) / scores.length);
		if (ring) ring.textContent = String(avg);
		if (circle) circle.style.strokeDashoffset = String(339 - (339 * avg) / 100);

		const warnings = Object.values(state.health)
			.map((h) => (h.warnings || []).length)
			.reduce((a, b) => a + b, 0);
		if (meta) meta.textContent = warnings ? `${warnings} warning(s) detected.` : 'All clear.';
	}

	function renderHealthReport() {
		if (!healthReportEl) {
			return;
		}
		const entries = Object.entries(state.health || {});
		if (!entries.length) {
			healthReportEl.innerHTML = '<div class="dsbpm-muted">No health data yet.</div>';
			return;
		}
		const html = entries
			.map(([slug, info]) => {
				const tone = info.score >= 80 ? 'is-good' : info.score >= 50 ? 'is-warn' : 'is-bad';
				const warnText = info.warnings && info.warnings.length ? info.warnings.join(' - ') : 'Clean';
				return `
					<div class="dsbpm-health-row">
						<span class="dsbpm-health-row__slug">${escapeHtml(slug)}</span>
						<span class="dsbpm-badge ${tone}">${info.score ?? '--'}</span>
						<span class="dsbpm-health-row__warn">${escapeHtml(warnText)}</span>
					</div>
				`;
			})
			.join('');
		healthReportEl.innerHTML = html;
	}

	function renderPayload() {
		const bar = el('dsbpm-payload-bar');
		const text = el('dsbpm-payload-text');
		if (!bar || !text) return;
		const total = state.payload.total || 0;
		if (!total) {
			bar.style.width = '0%';
			text.textContent = 'Total Payload: --';
			if (payloadMetaEl) {
				payloadMetaEl.textContent = 'Get a payload profile before you press install.';
			}
			return;
		}
		const threshold = 120 * 1024 * 1024;
		const percent = Math.min(100, Math.round((total / threshold) * 100));
		bar.style.width = `${percent}%`;
		bar.style.background = total > threshold
			? 'linear-gradient(90deg, #b91c1c, #f97316)'
			: 'linear-gradient(90deg, #0ea5e9, #10b981)';
		text.textContent = `Total Payload: ${(total / 1024 / 1024).toFixed(1)}MB`;
	}

	function renderPayloadMeta() {
		if (!payloadMetaEl) return;
		const total = state.payload.total || 0;
		const entries = Object.entries(state.payload.sizes || {});
		const heaviest = entries.reduce(
			(acc, [slug, size]) => (size > acc.size ? { slug, size } : acc),
			{ slug: '', size: 0 }
		);
		if (!total) {
			payloadMetaEl.textContent = 'Get a payload profile before you press install.';
			return;
		}
		const totalMb = (total / 1024 / 1024).toFixed(1);
		const heavyText = heaviest.slug ? `${heaviest.slug} ${(heaviest.size / 1024 / 1024).toFixed(1)}MB` : 'n/a';
		payloadMetaEl.textContent = `${state.queue.length} plugin${state.queue.length === 1 ? '' : 's'} - ${totalMb}MB - Heaviest: ${heavyText}`;
	}

	function renderMetrics() {
		const box = el('dsbpm-metrics');
		if (!box || !state.metrics) return;
		const metrics = state.metrics;
		box.innerHTML = `
			<div class="dsbpm-metric-row">
				<div class="dsbpm-metric-label">Installed</div>
				<div class="dsbpm-metric-value">${metrics.installed}</div>
			</div>
			<div class="dsbpm-metric-row">
				<div class="dsbpm-metric-label">Active</div>
				<div class="dsbpm-metric-value">${metrics.active}</div>
			</div>
			<div class="dsbpm-metric-row">
				<div class="dsbpm-metric-label">Inactive</div>
				<div class="dsbpm-metric-value">${metrics.inactive}</div>
			</div>
			<div class="dsbpm-metric-row">
				<div class="dsbpm-metric-label">Total Size</div>
				<div class="dsbpm-metric-value">${(metrics.totalSize / 1024 / 1024).toFixed(1)}MB</div>
			</div>
			<div class="dsbpm-metric-row">
				<div class="dsbpm-metric-label">Average Size</div>
				<div class="dsbpm-metric-value">${(metrics.averageSize / 1024 / 1024).toFixed(1)}MB</div>
			</div>
			<div class="dsbpm-metric-row">
				<div class="dsbpm-metric-label">Largest Plugin</div>
				<div class="dsbpm-metric-value">${escapeHtml(metrics.largest.name || '--')}</div>
			</div>
			<div class="dsbpm-metric-row">
				<div class="dsbpm-metric-label">Pinned</div>
				<div class="dsbpm-metric-value">${metrics.pinned}</div>
			</div>
			<div class="dsbpm-metric-row">
				<div class="dsbpm-metric-label">WP / PHP</div>
				<div class="dsbpm-metric-value">${metrics.wpVersion} / ${metrics.phpVersion}</div>
			</div>
		`;
		renderMetricsChart();
	}

	function renderMetricsChart() {
		if (!metricsChartEl || !state.metrics) return;
		const m = state.metrics;
		const total = Math.max(m.installed, 1);
		const segments = [
			{ label: 'Active', value: m.active, color: '#0ea5e9' },
			{ label: 'Inactive', value: m.inactive, color: '#f59e0b' },
			{ label: 'Pinned', value: m.pinned, color: '#6366f1' }
		];
		metricsChartEl.innerHTML = segments
			.map((seg) => {
				const width = Math.max(6, Math.round((seg.value / total) * 100));
				return `<div class="dsbpm-chart-segment" style="width:${width}%; background:${seg.color}"><span>${seg.label} ${seg.value}</span></div>`;
			})
			.join('');
	}

	function renderManaged() {
		const box = el('dsbpm-managed');
		if (!box) return;
		const searchInput = el('dsbpm-managed-search');
		const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

		let plugins = state.managed;
		if (query) {
			plugins = plugins.filter(
				(p) =>
					(p.name && p.name.toLowerCase().includes(query)) ||
					(p.slug && p.slug.toLowerCase().includes(query)) ||
					(p.note && p.note.toLowerCase().includes(query))
			);
		}

		if (!plugins.length) {
			box.innerHTML = query
				? '<div class="dsbpm-muted">No plugins match your search query.</div>'
				: '<div class="dsbpm-muted">No plugins found.</div>';
			return;
		}
		box.innerHTML = '';
		plugins.forEach((plugin) => {
			const row = document.createElement('div');
			row.className = 'dsbpm-managed-row';
			row.innerHTML = `
				<div class="dsbpm-managed-main">
					<div class="dsbpm-managed-title">${escapeHtml(plugin.name)}</div>
					<div class="dsbpm-managed-meta">${escapeHtml(plugin.slug)} - v${escapeHtml(plugin.version)}</div>
					${plugin.active ? '<span class="dsbpm-chip dsbpm-chip--on">Active</span>' : '<span class="dsbpm-chip dsbpm-chip--ghost">Inactive</span>'}
					${plugin.pinned ? '<span class="dsbpm-chip dsbpm-chip--pin">Pinned</span>' : ''}
					${plugin.autoUpdate ? '<span class="dsbpm-chip dsbpm-chip--on">Auto-Update On</span>' : ''}
				</div>
				<div class="dsbpm-managed-actions">
					<button class="button dsbpm-auto-update ${plugin.autoUpdate ? 'is-on' : ''}" data-plugin="${escapeHtml(plugin.file)}">${plugin.autoUpdate ? 'Auto-Update: On' : 'Auto-Update: Off'}</button>
					<button class="button dsbpm-pin ${plugin.pinned ? 'is-on' : ''}" data-plugin="${escapeHtml(plugin.file)}">${plugin.pinned ? 'Locked' : 'Lock'}</button>
					<input type="text" class="dsbpm-note" value="${escapeHtml(plugin.note || '')}" placeholder="Add note" data-plugin="${escapeHtml(plugin.file)}" />
				</div>
			`;
			box.appendChild(row);
		});
	}

	function renderDeps() {
		const svg = depsEl || el('dsbpm-deps');
		if (!svg || !queueEl) return;
		const cards = queueEl.querySelectorAll('.dsbpm-queue-card');
		const map = {};
		cards.forEach((card) => {
			const slug = card.dataset.slug;
			if (slug) map[slug] = card;
		});
		const rect = queueEl.getBoundingClientRect();
		svg.setAttribute('width', rect.width);
		svg.setAttribute('height', rect.height);
		svg.innerHTML = '';
		Object.keys(state.deps || {}).forEach((parent) => {
			const deps = state.deps[parent] || [];
			deps.forEach((child) => {
				const from = map[parent];
				const to = map[child];
				if (!from || !to) return;
				const fromRect = from.getBoundingClientRect();
				const toRect = to.getBoundingClientRect();
				const x1 = fromRect.left - rect.left + fromRect.width / 2;
				const y1 = fromRect.top - rect.top + fromRect.height;
				const x2 = toRect.left - rect.left + toRect.width / 2;
				const y2 = toRect.top - rect.top;
				const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
				const midY = (y1 + y2) / 2;
				path.setAttribute('d', `M ${x1} ${y1} C ${x1} ${midY}, ${x2} ${midY}, ${x2} ${y2}`);
				path.setAttribute('class', 'dsbpm-deps__line');
				svg.appendChild(path);
			});
		});
	}

	function parseCsv(text) {
		const input = String(text || '').replace(/^\uFEFF/, '');
		if (!input.trim()) return [];
		const rows = [];
		let row = [];
		let value = '';
		let inQuotes = false;

		for (let i = 0; i < input.length; i += 1) {
			const char = input[i];
			if (inQuotes) {
				if (char === '"') {
					const next = input[i + 1];
					if (next === '"') {
						value += '"';
						i += 1;
					} else {
						inQuotes = false;
					}
				} else {
					value += char;
				}
			} else if (char === '"') {
				inQuotes = true;
			} else if (char === ',') {
				row.push(value);
				value = '';
			} else if (char === '\n') {
				row.push(value);
				rows.push(row);
				row = [];
				value = '';
			} else if (char !== '\r') {
				value += char;
			}
		}

		row.push(value);
		if (row.length > 1 || row[0]) {
			rows.push(row);
		}

		if (!rows.length) return [];
		const headers = rows[0].map((h) => String(h).trim().toLowerCase());
		return rows
			.slice(1)
			.filter((cols) => cols.some((col) => String(col).trim() !== ''))
			.map((cols) => {
				const item = {};
				cols.forEach((cell, idx) => {
					const key = headers[idx] || `col${idx}`;
					item[key] = String(cell ?? '').trim();
				});
				return item;
			});
	}

	function normalizeCell(value) {
		if (value === null || typeof value === 'undefined') return '';
		if (value instanceof Date) return value.toISOString().slice(0, 10);
		return String(value).trim();
	}

	function parseXlsx(rows) {
		if (!rows || !rows.length) return [];
		const headerRow = rows[0].map((cell) => normalizeCell(cell).toLowerCase());
		const knownHeaders = ['slug', 'source', 'version', 'activate', 'network', 'force', 'name'];
		const hasHeader = headerRow.some((header) => knownHeaders.includes(header));
		const startIndex = hasHeader ? 1 : 0;
		const items = [];

		for (let i = startIndex; i < rows.length; i += 1) {
			const row = rows[i];
			if (!row || !row.length) continue;
			const item = {};
			if (hasHeader) {
				headerRow.forEach((header, idx) => {
					if (!header) return;
					item[header] = normalizeCell(row[idx]);
				});
			} else {
				item.slug = normalizeCell(row[0]);
				item.source = normalizeCell(row[1]);
				item.version = normalizeCell(row[2]);
				item.activate = normalizeCell(row[3]);
				item.network = normalizeCell(row[4]);
				item.force = normalizeCell(row[5]);
				item.name = normalizeCell(row[6]);
			}
			items.push(item);
		}

		return items;
	}

	function sanitizeDimension(value) {
		const cleaned = String(value || '').trim();
		if (!cleaned) return '';
		return /^\d+(\.\d+)?(px|%|rem|em|vh|vw)?$/.test(cleaned) ? cleaned : '';
	}

	function sanitizeRatio(value) {
		const cleaned = String(value || '').trim();
		if (!cleaned) return '';
		return /^\d+(\.\d+)?(\/\d+(\.\d+)?)?$/.test(cleaned) ? cleaned.replace(/\s+/g, '') : '';
	}

	function parsePartnerOffersRows(rows) {
		if (!rows || !rows.length) return [];
		const headers = rows[0].map((cell) => normalizeCell(cell).toLowerCase());
		const items = [];
		for (let i = 1; i < rows.length; i += 1) {
			const row = rows[i];
			if (!row || !row.length) continue;
			const item = {};
			headers.forEach((header, idx) => {
				if (!header) return;
				item[header] = normalizeCell(row[idx]);
			});
			items.push(item);
		}
		return items;
	}

	function normalizeOffer(raw) {
		const data = raw && typeof raw === 'object' ? raw : {};
		const offer = {
			type: String(data.type || '').toLowerCase(),
			id: String(data.id || data.slug || ''),
			title: String(data.title || data.name || ''),
			description: String(data.description || ''),
			badge: String(data.badge || ''),
			href: sanitizeUrl(data.href || data.url || ''),
			image: sanitizeUrl(data.image || ''),
			cta: String(data.cta || ''),
			placement: String(data.placement || '').toLowerCase(),
			width: String(data.width || ''),
			height: String(data.height || ''),
			maxWidth: String(data.maxWidth || data.maxwidth || ''),
			minHeight: String(data.minHeight || data.minheight || ''),
			aspectRatio: String(data.aspectRatio || data.aspectratio || ''),
			scaleMode: String(data.scaleMode || data.scalemode || '').toLowerCase(),
			active: normalizeBool(data.active ?? true),
			priority: Number(data.priority) || 999
		};
		return offer;
	}

	function buildInlineStyle(rules) {
		const entries = Object.entries(rules).filter(([, value]) => value);
		if (!entries.length) return '';
		const styles = entries.map(([key, value]) => `${key}:${value}`).join(';');
		return ` style="${styles}"`;
	}

	function renderPartnerOffers(offers, sourceLabel) {
		if (!promoHeroEl || !promoGridEl) return;
		const normalized = (offers || [])
			.map(normalizeOffer)
			.filter((offer) => offer.active && (offer.title || offer.description));

		if (!normalized.length) {
			promoHeroEl.innerHTML = '<div class="dsbpm-muted">Partner spotlight will appear here.</div>';
			promoGridEl.innerHTML = '<div class="dsbpm-muted">No partner offers available yet.</div>';
			if (promoSourceEl) {
				promoSourceEl.textContent = 'Partner feed unavailable.';
			}
			return;
		}

		normalized.sort((a, b) => (a.priority || 999) - (b.priority || 999));
		const hero =
			normalized.find((offer) => ['hero', 'spotlight', 'feature'].includes(offer.placement) || ['hero', 'spotlight'].includes(offer.type)) ||
			normalized[0];
		const grid = normalized.filter((offer) => offer !== hero);

		const heroRatio = sanitizeRatio(hero.aspectRatio);
		const heroMinHeight = sanitizeDimension(hero.minHeight);
		const heroStyle = buildInlineStyle({
			'--dsbpm-hero-ratio': heroRatio || '',
			'min-height': heroMinHeight || ''
		});
		const heroImage = hero.image ? `<img src="${escapeHtml(hero.image)}" alt="" loading="lazy" />` : '<div class="dsbpm-promo-hero__placeholder"></div>';
		const heroHref = withTrackingUrl(hero.href, 'partner-spotlight') || hero.href || '#';
		const heroCta = escapeHtml(hero.cta || 'Learn more');
		const heroBadge = hero.badge ? `<span class="dsbpm-pill dsbpm-pill--soft">${escapeHtml(hero.badge)}</span>` : '';
		const heroLabel = escapeHtml(hero.title || 'Partner spotlight');

		promoHeroEl.innerHTML = `
			<a class="dsbpm-promo-hero__link" href="${escapeHtml(heroHref)}" target="_blank" rel="noopener noreferrer" aria-label="${heroLabel}">
				<div class="dsbpm-promo-hero__content">
					${heroBadge}
					<div class="dsbpm-promo-hero__title">${escapeHtml(hero.title || 'Partner spotlight')}</div>
					<div class="dsbpm-promo-hero__desc">${escapeHtml(hero.description || '')}</div>
					<div class="dsbpm-promo-hero__cta">
						<span class="dsbpm-button">${heroCta}</span>
					</div>
				</div>
				<div class="dsbpm-promo-hero__media"${heroStyle}>${heroImage}</div>
			</a>
		`;

		if (!grid.length) {
			promoGridEl.innerHTML = '<div class="dsbpm-muted">More partner offers coming soon.</div>';
		} else {
			promoGridEl.innerHTML = grid
				.map((offer) => {
					const offerRatio = sanitizeRatio(offer.aspectRatio);
					const offerMinHeight = sanitizeDimension(offer.minHeight);
					const offerStyle = buildInlineStyle({
						'--dsbpm-offer-ratio': offerRatio || '',
						'min-height': offerMinHeight || ''
					});
					const image = offer.image ? `<img src="${escapeHtml(offer.image)}" alt="" loading="lazy" />` : '<div class="dsbpm-offer-card__placeholder"></div>';
					const badge = offer.badge ? `<span class="dsbpm-offer-card__badge">${escapeHtml(offer.badge)}</span>` : '';
					const cta = escapeHtml(offer.cta || 'View offer');
					const offerContext = offer.id ? `partner-offer-${offer.id}` : `partner-offer-${slugify(offer.title || 'offer')}`;
					const href = withTrackingUrl(offer.href, offerContext) || offer.href || '#';
					const label = escapeHtml(offer.title || 'Partner offer');
					return `
						<a class="dsbpm-offer-card" href="${escapeHtml(href)}" target="_blank" rel="noopener noreferrer" aria-label="${label}">
							<div class="dsbpm-offer-card__media"${offerStyle}>
								${image}
								${badge}
							</div>
							<div class="dsbpm-offer-card__body">
								<div class="dsbpm-offer-card__title">${escapeHtml(offer.title || 'Partner offer')}</div>
								<div class="dsbpm-offer-card__desc">${escapeHtml(offer.description || '')}</div>
								<span class="dsbpm-link">${cta}</span>
							</div>
						</a>
					`;
				})
				.join('');
		}

		if (promoSourceEl) {
			const label =
				sourceLabel === 'remote'
					? 'Source: remote feed'
					: sourceLabel === 'local'
						? 'Source: local backup'
						: sourceLabel
							? `Source: ${sourceLabel}`
							: 'Source: local backup';
			promoSourceEl.textContent = `${label} - ${normalized.length} offer${normalized.length === 1 ? '' : 's'}`;
		}
	}

	function handleFile(file) {
		if (!file) return;
		const lower = file.name.toLowerCase();
		if (lower.endsWith('.xlsx')) {
			if (typeof window.readXlsxFile !== 'function') {
				toast('XLSX parser is not available. Please reload the page.', 'error');
				return;
			}
			window.readXlsxFile(file).then((rows) => {
				const parsed = parseXlsx(rows).map(normalizeItem);
				addItems(parsed);
				toast(`Imported ${parsed.length} from XLSX`, 'success');
			});
			return;
		}
		const reader = new FileReader();
		reader.onload = (event) => {
			const content = String(event.target.result || '');
			const rows = parseCsv(content).map(normalizeItem);
			addItems(rows);
			toast(`Imported ${rows.length} from CSV`, 'success');
		};
		reader.readAsText(file);
	}

	function handleSlugsInput() {
		const input = el('dsbpm-slug-input');
		if (!input) return;
		const lines = input.value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
		const rows = lines.map((line) => normalizeItem({ slug: line, activate: true }));
		addItems(rows);
		input.value = '';
		toast(`Added ${rows.length} slugs`, 'success');
	}

	function updateQueueWithHealth() {
		state.queue = state.queue.map((item) => {
			const health = state.health[item.slug];
			if (health) {
				item.warnings = health.warnings || [];
				if (health.warnings && health.warnings.length && item.status === 'pending') {
					item.status = 'warn';
				}
			}
			return item;
		});
		renderQueue();
		renderHealth();
		renderHealthReport();
	}

	function updateQueueWithPayload() {
		state.queue = state.queue.map((item) => {
			if (state.payload.sizes[item.slug]) {
				item.size = state.payload.sizes[item.slug];
			}
			return item;
		});
		renderQueue();
		renderPayload();
		renderPayloadMeta();
	}

	async function runPreflight() {
		const slugs = state.queue.map((item) => item.slug).filter(Boolean);
		if (!slugs.length) return;
		const res = await ajax('dsbpm_preflight', { slugs });
		if (res.success) {
			state.health = res.data.health || {};
			updateQueueWithHealth();
			toast('Pre-flight completed', 'success');
		} else {
			toast(resolveError(res, 'Pre-flight failed'), 'error');
		}
	}

	const runPreflightDebounced = debounce(runPreflight, 350);

	async function runPayload() {
		const slugs = state.queue.map((item) => item.slug).filter(Boolean);
		if (!slugs.length) return;
		const res = await ajax('dsbpm_payload', { slugs });
		if (res.success) {
			state.payload = res.data;
			updateQueueWithPayload();
			toast('Payload calculated', 'info');
		} else {
			toast(resolveError(res, 'Payload calculation failed'), 'error');
		}
	}

	const runPayloadDebounced = debounce(runPayload, 350);

	async function runMetrics() {
		const res = await ajax('dsbpm_metrics');
		if (res.success) {
			state.metrics = res.data;
			renderMetrics();
			toast('Site metrics refreshed', 'info');
		} else {
			toast(resolveError(res, 'Metrics failed'), 'error');
		}
	}

	const runMetricsDebounced = debounce(runMetrics, 800);

	async function runSimulation() {
		if (!state.queue.length) return;
		const res = await ajax('dsbpm_simulate', { items: state.queue });
		if (res.success && res.data.report) {
			state.queue = state.queue.map((item) => {
				const found = res.data.report.find((row) => row.slug === item.slug);
				if (found && found.issues && found.issues.length) {
					item.warnings = (item.warnings || []).concat(found.issues);
					item.status = 'simulated';
				}
				return item;
			});
			renderQueue();
			toast('Simulation ready', 'info');
		} else if (!res.success) {
			toast(resolveError(res, 'Simulation failed'), 'error');
		}
	}

	async function runVulnCheck() {
		const slugs = state.queue.map((item) => item.slug).filter(Boolean);
		if (!slugs.length) return;
		const keyInput = el('dsbpm-wpscan-key');
		const key = keyInput ? keyInput.value : '';
		const res = await ajax('dsbpm_vuln_check', { slugs, key });
		if (res.success) {
			state.vuln = res.data.results || {};
			state.queue = state.queue.map((item) => {
				const scan = state.vuln[item.slug];
				if (scan && scan.issues && scan.issues.length) {
					item.warnings = (item.warnings || []).concat(['Vulnerabilities detected']);
				}
				if (scan && scan.critical) {
					item.vulnCritical = true;
					item.status = 'blocked';
				}
				return item;
			});
			renderQueue();
			toast('Security check complete', 'info');
		} else {
			toast(resolveError(res, 'Security check failed'), 'error');
		}
	}

	async function runPluginsList() {
		const res = await ajax('dsbpm_plugins_list');
		if (res.success) {
			state.managed = res.data.plugins || [];
			renderManaged();
		} else {
			toast(resolveError(res, 'Plugin list failed'), 'error');
		}
	}

	async function loadPartnerOffers() {
		if (!promoHeroEl || !promoGridEl) return;
		const res = await ajax('dsbpm_partner_offers');
		if (res.success && res.data && Array.isArray(res.data.offers)) {
			state.partnerOffers = res.data.offers;
			state.partnerSource = res.data.source || '';
			renderPartnerOffers(state.partnerOffers, state.partnerSource);
			return;
		}

		await loadPartnerOffersFallback();
	}

	async function loadPartnerOffersFallback() {
		if (DSBPM_DATA.partnerOffersBackupUrl && typeof window.readXlsxFile === 'function') {
			try {
				const response = await fetch(DSBPM_DATA.partnerOffersBackupUrl, { credentials: 'same-origin' });
				if (response.ok) {
					const buffer = await response.arrayBuffer();
					const rows = await window.readXlsxFile(buffer);
					const items = parsePartnerOffersRows(rows);
					state.partnerOffers = items;
					state.partnerSource = 'local';
					renderPartnerOffers(items, 'local');
					return;
				}
			} catch (error) {
				// fall back to CSV below
			}
		}

		if (DSBPM_DATA.partnerOffersCsvBackupUrl) {
			try {
				const response = await fetch(DSBPM_DATA.partnerOffersCsvBackupUrl, { credentials: 'same-origin' });
				if (response.ok) {
					const text = await response.text();
					const items = parseCsv(text);
					state.partnerOffers = items;
					state.partnerSource = 'local';
					renderPartnerOffers(items, 'local');
					return;
				}
			} catch (error) {
				// ignore
			}
		}

		if (promoSourceEl) {
			promoSourceEl.textContent = 'Partner feed unavailable.';
		}
	}

	function showCredentialsModal(formHtml) {
		const modal = el('dsbpm-credentials-modal');
		const body = el('dsbpm-credentials-body');
		if (!modal || !body) return;
		body.innerHTML = formHtml || 'Filesystem credentials required to continue.';
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
	}

	function hideCredentialsModal() {
		const modal = el('dsbpm-credentials-modal');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}

	function showQueueCompleteDialog(message) {
		const modal = el('dsbpm-queue-complete');
		const body = el('dsbpm-queue-complete-body');
		if (!modal || !body) return;
		body.textContent = message || 'Queue complete.';
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
	}

	function hideQueueCompleteDialog() {
		const modal = el('dsbpm-queue-complete');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}

	async function healthHandshake(pluginFile) {
		try {
			const response = await fetch(DSBPM_DATA.restHealthUrl, { credentials: 'same-origin' });
			if (response.ok) return true;
		} catch (error) {
			return false;
		}
		return false;
	}

	async function deactivatePlugin(pluginFile) {
		if (!pluginFile) return;
		await ajax('dsbpm_deactivate', { plugins: [pluginFile] });
	}

	async function runInstallQueue() {
		if (state.installing) return;
		state.installing = true;
		stopRequested = false;

		if (stopInstallBtn) stopInstallBtn.style.display = 'inline-block';
		if (startInstallBtn) startInstallBtn.disabled = true;

		const reliability = el('dsbpm-reliability');
		if (reliability && reliability.checked) {
			const res = await ajax('dsbpm_enqueue', { items: state.queue });
			state.installing = false;
			if (stopInstallBtn) stopInstallBtn.style.display = 'none';
			if (startInstallBtn) startInstallBtn.disabled = false;

			if (!res.success) {
				toast(resolveError(res, 'Failed to enqueue.'), 'error');
				return;
			}
			state.queue = state.queue.map((item) => {
				item.status = 'queued';
				item.progress = 100;
				return item;
			});
			renderQueue();
			runMetrics();
			showQueueCompleteDialog('Queue scheduled in high-reliability mode.');
			toast('Queued in high-reliability mode', 'success');
			return;
		}

		for (; state.queueIndex < state.queue.length; state.queueIndex += 1) {
			if (stopRequested) {
				toast('Installation stopped by user.', 'warn');
				break;
			}

			const item = state.queue[state.queueIndex];
			if (item.vulnCritical) {
				item.status = 'blocked';
				item.progress = 100;
				renderQueue();
				continue;
			}
			item.status = 'installing';
			item.progress = 20;
			renderQueue();

			const res = await ajax('dsbpm_install', { item });
			if (!res.success) {
				if (res.data && res.data.form) {
					item.status = 'waiting_credentials';
					item.progress = 10;
					renderQueue();
					showCredentialsModal(res.data.form);
					state.installing = false;
					if (stopInstallBtn) stopInstallBtn.style.display = 'none';
					if (startInstallBtn) startInstallBtn.disabled = false;
					return;
				}
				item.status = 'rescued';
				item.warnings = (item.warnings || []).concat([resolveError(res, 'Install failed')]);
				item.progress = 100;
				renderQueue();
				continue;
			}

			item.progress = 80;
			item.status = res.data.status || 'installed';
			item.pluginFile = res.data.pluginFile || '';
			if (item.status === 'activation_failed') {
				item.status = 'installed';
				item.warnings = (item.warnings || []).concat(['Activation failed; plugin remains deactivated.']);
			}
			renderQueue();

			if (item.activate && item.pluginFile) {
				const ok = await healthHandshake(item.pluginFile);
				if (!ok) {
					await deactivatePlugin(item.pluginFile);
					item.status = 'installed';
					item.warnings = (item.warnings || []).concat(['Activation failed health check; plugin was deactivated.']);
				}
			}

			item.progress = 100;
			if (item.status === 'installing') {
				item.status = 'installed';
			}
			renderQueue();
			runMetricsDebounced();
		}

		state.installing = false;
		if (stopInstallBtn) stopInstallBtn.style.display = 'none';
		if (startInstallBtn) startInstallBtn.disabled = false;

		if (!stopRequested) {
			state.queueIndex = 0;
			runMetrics();
			showQueueCompleteDialog('Your queue has finished processing. Metrics are refreshed.');
			toast('Queue complete', 'success');
		} else {
			stopRequested = false;
		}
	}

	async function runSmartSort() {
		const res = await ajax('dsbpm_sort_queue', { items: state.queue });
		if (res.success && res.data.items) {
			state.queue = res.data.items.map(normalizeItem);
			state.deps = res.data.deps || {};
			renderQueue();
			toast('Queue reordered by dependency', 'info');
		} else if (!res.success) {
			toast(resolveError(res, 'Smart sort failed'), 'error');
		}
	}

	async function runRemoteFetch() {
		const input = el('dsbpm-remote-url');
		if (!input || !input.value) return;
		const res = await ajax('dsbpm_remote_list', { url: input.value });
		if (res.success && res.data.items) {
			const items = res.data.items.map(normalizeItem);
			addItems(items);
			if (remoteStatusEl) {
				remoteStatusEl.textContent = `Fetched ${items.length} item(s)`;
			}
			toast('Remote list added', 'success');
		} else if (!res.success) {
			toast(resolveError(res, 'Remote fetch failed'), 'error');
		}
	}

	function saveFavorite() {
		const payload = state.queue.map((item) => ({
			slug: item.slug,
			source: item.source,
			version: item.version,
			activate: item.activate,
			network: item.network
		}));
		localStorage.setItem('dsbpm-favorite-stack', JSON.stringify(payload));
		toast('Favorite saved locally', 'success');
	}

	function loadFavorite() {
		const raw = localStorage.getItem('dsbpm-favorite-stack');
		if (!raw) return;
		try {
			const items = JSON.parse(raw);
			addItems(items.map(normalizeItem));
			toast('Favorite loaded', 'info');
		} catch (error) {
			toast('Favorite could not be loaded', 'error');
		}
	}

	function clearQueue() {
		state.queue = [];
		state.deps = {};
		state.queueIndex = 0;
		state.installing = false;
		renderQueue();
		renderHealth();
		renderPayload();
		renderPayloadMeta();
	}

	function downloadTemplate(type) {
		let content = '';
		let name = '';
		if (type === 'csv') {
			content = 'slug,source,version,activate,network,force\nhello-dolly,,latest,1,0,0\nwordpress-seo,,latest,1,0,0';
			name = 'dazestack-stack-template.csv';
		} else {
			content = JSON.stringify(
				[
					{ slug: 'woocommerce', activate: 1 },
					{ slug: 'wordpress-seo', activate: 1, version: 'latest' }
				],
				null,
				2
			);
			name = 'dazestack-stack-template.json';
		}
		const blob = new Blob([content], { type: 'text/plain' });
		const a = document.createElement('a');
		a.href = URL.createObjectURL(blob);
		a.download = name;
		a.click();
		URL.revokeObjectURL(a.href);
	}

	function moveQueueItem(index, direction) {
		const target = index + direction;
		if (target < 0 || target >= state.queue.length) return;
		const temp = state.queue[index];
		state.queue[index] = state.queue[target];
		state.queue[target] = temp;
		renderQueue();
	}

	function removeQueueItem(index) {
		state.queue.splice(index, 1);
		renderQueue();
	}

	async function storeCredentials(form) {
		const formData = new FormData(form);
		const payload = {};
		formData.forEach((value, key) => {
			payload[key] = value;
		});
		const res = await ajax('dsbpm_store_credentials', payload);
		if (res.success) {
			hideCredentialsModal();
			runInstallQueue();
		} else if (res.data && res.data.message) {
			toast(res.data.message, 'error');
		}
	}

	function bindEvents() {
		const fileInput = el('dsbpm-file');
		const dropzone = el('dsbpm-dropzone');

		if (fileInput) {
			fileInput.addEventListener('change', (event) => handleFile(event.target.files[0]));
		}

		if (dropzone) {
			dropzone.addEventListener('dragover', (event) => {
				event.preventDefault();
				dropzone.style.borderColor = '#0f766e';
			});
			dropzone.addEventListener('dragleave', () => {
				dropzone.style.borderColor = 'rgba(15, 118, 110, 0.25)';
			});
			dropzone.addEventListener('drop', (event) => {
				event.preventDefault();
				dropzone.style.borderColor = 'rgba(15, 118, 110, 0.25)';
				handleFile(event.dataTransfer.files[0]);
			});
		}

		const addSlugs = el('dsbpm-add-slugs');
		if (addSlugs) addSlugs.addEventListener('click', handleSlugsInput);

		const preflight = el('dsbpm-preflight');
		if (preflight) preflight.addEventListener('click', runPreflight);

		const simulateBtn = el('dsbpm-simulate');
		if (simulateBtn) simulateBtn.addEventListener('click', runSimulation);

		const payloadBtn = el('dsbpm-payload');
		if (payloadBtn) payloadBtn.addEventListener('click', runPayload);

		const startInstall = el('dsbpm-start-install');
		if (startInstall) startInstall.addEventListener('click', runInstallQueue);

		if (stopInstallBtn) {
			stopInstallBtn.addEventListener('click', () => {
				stopRequested = true;
				stopInstallBtn.disabled = true;
				stopInstallBtn.textContent = 'Stopping...';
				setTimeout(() => {
					if (stopInstallBtn) {
						stopInstallBtn.disabled = false;
						stopInstallBtn.textContent = 'Stop Install';
					}
				}, 1000);
			});
		}

		const refreshMetrics = el('dsbpm-refresh-metrics');
		if (refreshMetrics) refreshMetrics.addEventListener('click', runMetrics);

		const vulnBtn = el('dsbpm-vuln-check');
		if (vulnBtn) vulnBtn.addEventListener('click', runVulnCheck);

		const sortQueue = el('dsbpm-sort-queue');
		if (sortQueue) sortQueue.addEventListener('click', runSmartSort);

		const clearQueueBtn = el('dsbpm-clear-queue');
		if (clearQueueBtn) clearQueueBtn.addEventListener('click', clearQueue);

		const remoteFetch = el('dsbpm-remote-fetch');
		if (remoteFetch) remoteFetch.addEventListener('click', runRemoteFetch);

		const deactivateAll = el('dsbpm-deactivate-all');
		if (deactivateAll) {
			deactivateAll.addEventListener('click', async () => {
				await ajax('dsbpm_deactivate_all');
				runMetrics();
			});
		}

		const deleteAll = el('dsbpm-delete-all');
		if (deleteAll) {
			deleteAll.addEventListener('click', async () => {
				if (!window.confirm(__('Delete all plugins except DazeStack?'))) return;
				await ajax('dsbpm_delete_all');
				runMetrics();
			});
		}

		const saveFavoriteBtn = el('dsbpm-save-favorite');
		if (saveFavoriteBtn) saveFavoriteBtn.addEventListener('click', saveFavorite);

		const loadFavoriteBtn = el('dsbpm-load-favorite');
		if (loadFavoriteBtn) loadFavoriteBtn.addEventListener('click', loadFavorite);

		const openRecipesBtn = el('dsbpm-open-recipes');
		if (openRecipesBtn) openRecipesBtn.addEventListener('click', openRecipesModal);

		const recipesCloseBtn = el('dsbpm-recipes-close');
		if (recipesCloseBtn) recipesCloseBtn.addEventListener('click', closeRecipesModal);

		const saveRecipeBtn = el('dsbpm-save-recipe-btn');
		if (saveRecipeBtn) saveRecipeBtn.addEventListener('click', openSaveRecipeModal);

		const saveRecipeConfirm = el('dsbpm-save-recipe-confirm');
		if (saveRecipeConfirm) saveRecipeConfirm.addEventListener('click', confirmSaveRecipe);

		const saveRecipeClose = el('dsbpm-save-recipe-close');
		if (saveRecipeClose) saveRecipeClose.addEventListener('click', closeSaveRecipeModal);

		const exportWpCliBtn = el('dsbpm-export-wp-cli');
		if (exportWpCliBtn) exportWpCliBtn.addEventListener('click', runExportWpCli);

		const wpCliCloseBtn = el('dsbpm-wpcli-close');
		if (wpCliCloseBtn) wpCliCloseBtn.addEventListener('click', closeWpCliModal);

		const wpCliCopyBtn = el('dsbpm-wpcli-copy');
		if (wpCliCopyBtn) {
			wpCliCopyBtn.addEventListener('click', () => {
				const textarea = el('dsbpm-wpcli-output');
				if (textarea && textarea.value) {
					navigator.clipboard.writeText(textarea.value);
					toast('Copied WP-CLI command to clipboard!', 'success');
				}
			});
		}

		const managedSearch = el('dsbpm-managed-search');
		if (managedSearch) {
			managedSearch.addEventListener('input', renderManaged);
		}

		const modalClose = el('dsbpm-credentials-close');
		if (modalClose) modalClose.addEventListener('click', hideCredentialsModal);

		const queueCompleteClose = el('dsbpm-queue-complete-close');
		if (queueCompleteClose) queueCompleteClose.addEventListener('click', hideQueueCompleteDialog);

		const modal = el('dsbpm-credentials-modal');
		if (modal) {
			modal.addEventListener('submit', (event) => {
				const form = event.target;
				if (form && form.tagName === 'FORM') {
					event.preventDefault();
					storeCredentials(form);
				}
			});
		}

		const managed = el('dsbpm-managed');
		if (managed) {
			managed.addEventListener('click', async (event) => {
				const pinButton = event.target.closest('.dsbpm-pin');
				if (pinButton) {
					const plugin = pinButton.getAttribute('data-plugin');
					const pin = pinButton.classList.contains('is-on') ? 0 : 1;
					const res = await ajax('dsbpm_toggle_pin', { plugin, pin });
					if (res.success) {
						runPluginsList();
						runMetrics();
					}
					return;
				}

				const autoButton = event.target.closest('.dsbpm-auto-update');
				if (autoButton) {
					const plugin = autoButton.getAttribute('data-plugin');
					const res = await ajax('dsbpm_toggle_auto_update', { file: plugin });
					if (res.success) {
						toast(res.data.enabled ? 'Auto-updates enabled' : 'Auto-updates disabled', 'info');
						runPluginsList();
						runMetrics();
					}
					return;
				}
			});
			managed.addEventListener('change', async (event) => {
				const input = event.target.closest('.dsbpm-note');
				if (!input) return;
				const plugin = input.getAttribute('data-plugin');
				const note = input.value;
				await ajax('dsbpm_save_note', { plugin, note });
			});
		}

		if (queueEl) {
			queueEl.addEventListener('click', (event) => {
				const card = event.target.closest('.dsbpm-queue-card');
				if (!card) return;
				const index = parseInt(card.dataset.index, 10);
				const actionBtn = event.target.closest('[data-action]');
				if (!Number.isInteger(index) || index < 0) return;
				if (actionBtn) {
					const action = actionBtn.getAttribute('data-action');
					if (action === 'remove') removeQueueItem(index);
					if (action === 'move-up') moveQueueItem(index, -1);
					if (action === 'move-down') moveQueueItem(index, 1);
				}
			});
		}

		document.addEventListener('keydown', (event) => {
			if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
				event.preventDefault();
				runInstallQueue();
			} else if (event.key === 'Escape') {
				closeRecipesModal();
				closeSaveRecipeModal();
				closeWpCliModal();
				hideCredentialsModal();
				hideQueueCompleteDialog();
			}
		});

		document.querySelectorAll('.dsbpm-modal').forEach((modal) => {
			modal.addEventListener('click', (event) => {
				if (event.target === modal) {
					closeRecipesModal();
					closeSaveRecipeModal();
					closeWpCliModal();
					hideCredentialsModal();
					hideQueueCompleteDialog();
				}
			});
		});

		document.body.addEventListener('click', (event) => {
			const help = event.target.closest('.dsbpm-help');
			if (!help) return;
			event.preventDefault();
			toast(help.getAttribute('data-help') || 'Help text', 'info');
		});

		const templateCsv = el('dsbpm-template-csv');
		if (templateCsv) templateCsv.addEventListener('click', () => downloadTemplate('csv'));
		const templateJson = el('dsbpm-template-json');
		if (templateJson) templateJson.addEventListener('click', () => downloadTemplate('json'));

		window.addEventListener('resize', debounce(renderDeps, 100));
	}

	async function openRecipesModal() {
		const modal = el('dsbpm-recipes-modal');
		if (!modal) return;
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
		await loadRecipesList();
	}

	function closeRecipesModal() {
		const modal = el('dsbpm-recipes-modal');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}

	async function loadRecipesList() {
		const body = el('dsbpm-recipes-body');
		if (!body) return;
		body.innerHTML = '<div class="dsbpm-muted">Loading recipes...</div>';
		const res = await ajax('dsbpm_get_recipes');
		if (!res.success || !res.data.recipes) {
			body.innerHTML = '<div class="dsbpm-muted">Failed to load recipes.</div>';
			return;
		}
		const recipes = res.data.recipes;
		body.innerHTML = recipes
			.map((recipe) => {
				const badge = recipe.badge ? `<span class="dsbpm-badge">${escapeHtml(recipe.badge)}</span>` : '';
				const count = (recipe.items || []).length;
				const isBuiltin = recipe.isBuiltin;
				const deleteBtn = !isBuiltin
					? `<button class="button dsbpm-button--danger dsbpm-delete-recipe" data-id="${escapeHtml(recipe.id)}">Delete</button>`
					: '';
				return `
					<div class="dsbpm-recipe-card">
						<div class="dsbpm-recipe-card__header">
							<div class="dsbpm-recipe-card__title">${escapeHtml(recipe.title)} ${badge}</div>
							<div class="dsbpm-recipe-card__count">${count} plugin${count === 1 ? '' : 's'}</div>
						</div>
						<div class="dsbpm-recipe-card__desc">${escapeHtml(recipe.description || '')}</div>
						<div class="dsbpm-recipe-card__actions">
							<button class="button button-primary dsbpm-load-recipe" data-id="${escapeHtml(recipe.id)}">Load Stack</button>
							${deleteBtn}
						</div>
					</div>
				`;
			})
			.join('');

		body.querySelectorAll('.dsbpm-load-recipe').forEach((btn) => {
			btn.addEventListener('click', () => {
				const id = btn.getAttribute('data-id');
				const found = recipes.find((r) => r.id === id);
				if (found && found.items) {
					addItems(found.items.map(normalizeItem));
					closeRecipesModal();
					toast(`Loaded stack recipe: ${found.title}`, 'success');
				}
			});
		});

		body.querySelectorAll('.dsbpm-delete-recipe').forEach((btn) => {
			btn.addEventListener('click', async () => {
				const id = btn.getAttribute('data-id');
				if (!window.confirm(__('Delete this saved recipe?'))) return;
				const delRes = await ajax('dsbpm_delete_recipe', { id });
				if (delRes.success) {
					toast(__('Recipe deleted'), 'info');
					loadRecipesList();
				}
			});
		});
	}

	function openSaveRecipeModal() {
		if (!state.queue.length) {
			toast(__('Queue is empty. Add plugins to save a recipe.'), 'warn');
			return;
		}
		const modal = el('dsbpm-save-recipe-modal');
		if (!modal) return;
		modal.classList.add('is-open');
		modal.setAttribute('aria-hidden', 'false');
	}

	function closeSaveRecipeModal() {
		const modal = el('dsbpm-save-recipe-modal');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}

	async function confirmSaveRecipe() {
		const titleInput = el('dsbpm-recipe-title-input');
		const descInput = el('dsbpm-recipe-desc-input');
		const title = titleInput ? titleInput.value.trim() : '';
		const description = descInput ? descInput.value.trim() : '';

		if (!title) {
			toast(__('Please provide a title for your recipe.'), 'error');
			return;
		}

		const res = await ajax('dsbpm_save_recipe', { title, description, items: state.queue });
		if (res.success) {
			closeSaveRecipeModal();
			if (titleInput) titleInput.value = '';
			if (descInput) descInput.value = '';
			toast(__('Recipe saved successfully!'), 'success');
		} else {
			toast(resolveError(res, 'Failed to save recipe.'), 'error');
		}
	}

	async function runExportWpCli() {
		if (!state.queue.length) {
			toast('Queue is empty. Add plugins to generate WP-CLI commands.', 'warn');
			return;
		}
		const res = await ajax('dsbpm_export_wp_cli', { items: state.queue });
		if (res.success && res.data.command) {
			const textarea = el('dsbpm-wpcli-output');
			if (textarea) textarea.value = res.data.command;
			const modal = el('dsbpm-wpcli-modal');
			if (modal) {
				modal.classList.add('is-open');
				modal.setAttribute('aria-hidden', 'false');
			}
		} else {
			toast(resolveError(res, 'Failed to generate WP-CLI command.'), 'error');
		}
	}

	function closeWpCliModal() {
		const modal = el('dsbpm-wpcli-modal');
		if (!modal) return;
		modal.classList.remove('is-open');
		modal.setAttribute('aria-hidden', 'true');
	}
	}

	bindEvents();

	if (!DSBPM_DATA.hasActionScheduler) {
		const reliabilityWrap = el('dsbpm-reliability')?.closest('.dsbpm-control');
		if (reliabilityWrap) {
			reliabilityWrap.style.display = 'none';
		}
	}

	runMetrics();
	runPluginsList();
	loadPartnerOffers();
	renderQueue();
})();
