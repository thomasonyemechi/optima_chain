import './bootstrap';

const updateInventoryBalance = (form) => {
	const stockInput = form.querySelector('[data-stock-input]');
	const salesInput = form.querySelector('[data-sales-input]');
	const balanceOutput = form.querySelector('[data-inventory-balance]');

	if (!stockInput || !salesInput || !balanceOutput) {
		return;
	}

	const stock = Number(stockInput.value) || 0;
	const sales = Number(salesInput.value) || 0;
	balanceOutput.textContent = `${Math.max(0, stock - sales).toLocaleString()} units`;
};

document.querySelectorAll('[data-sales-input]').forEach((input) => {
	input.addEventListener('input', () => updateInventoryBalance(input.form));
});

document.querySelectorAll('[data-weekly-request]').forEach((form) => {
	const quantityInput = form.querySelector('[name="requested_qty"]');
	const badge = form.querySelector('[data-request-status-badge]');

	if (!quantityInput || !badge) {
		return;
	}

	const states = {
		neutral: ['Awaiting estimate', 'bg-slate-100', 'text-slate-600'],
		belowForecast: ['Below forecast band', 'bg-amber-50', 'text-amber-700'],
		approved: ['Auto-approve eligible', 'bg-emerald-50', 'text-emerald-700'],
		flagged: ['Flagged for review', 'bg-amber-50', 'text-amber-700'],
		stockout: ['Stockout risk', 'bg-red-50', 'text-red-700'],
	};

	const updateStatus = () => {
		const requested = Number(quantityInput.value);
		const low = Number(form.dataset.forecastLow) || 0;
		const high = Number(form.dataset.forecastHigh) || 0;
		const expected = Number(form.dataset.forecastExpected) || 0;
		const availableStock = form.dataset.availableStock === '' ? null : Number(form.dataset.availableStock);
		let state = 'neutral';

		if (quantityInput.value !== '' && requested > high) {
			state = 'flagged';
		} else if (quantityInput.value !== '' && availableStock !== null && requested > availableStock) {
			state = 'stockout';
		} else if (quantityInput.value !== '' && requested < low) {
			state = 'belowForecast';
		} else if (quantityInput.value !== '' && requested <= expected) {
			state = 'approved';
		} else if (quantityInput.value !== '') {
			state = 'approved';
		}

		badge.className = `rounded-full px-3 py-1.5 text-xs font-semibold ${states[state].slice(1).join(' ')}`;
		badge.textContent = states[state][0];
	};

	quantityInput.addEventListener('input', updateStatus);

	if (quantityInput.value !== '' && badge.textContent.trim() === states.neutral[0]) {
		updateStatus();
	}
});
