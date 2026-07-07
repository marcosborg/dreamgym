import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const bookingFlow = document.querySelector('[data-booking-flow]');
    const purchaseForm = document.getElementById('purchase-form');

    const showBookingFlow = () => {
        bookingFlow?.classList.remove('hidden');
        purchaseForm?.classList.add('hidden');
    };

    const showPurchaseFlow = () => {
        bookingFlow?.classList.add('hidden');
        purchaseForm?.classList.remove('hidden');
    };

    const selectBookingType = (value, shouldScroll = false) => {
        document.querySelectorAll('[data-booking-card]').forEach((card) => {
            card.classList.toggle('is-selected', card.dataset.selectBookingType === value);
        });

        showBookingFlow();

        if (shouldScroll) {
            document.querySelectorAll('[data-product-card]').forEach((card) => {
                card.classList.remove('is-selected');
            });
        }

        document.querySelectorAll('[data-booking-option]').forEach((option) => {
            const isSelected = option.dataset.bookingOption === value;
            option.classList.toggle('is-selected', isSelected);
            option.querySelector('input[type="radio"]').checked = isSelected;
        });

        if (shouldScroll) {
            document.getElementById('booking-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    document.querySelectorAll('[data-booking-card]').forEach((card) => {
        card.addEventListener('click', (event) => {
            event.preventDefault();
            selectBookingType(card.dataset.selectBookingType, true);
        });
    });

    document.querySelectorAll('[data-booking-option] input[type="radio"]').forEach((input) => {
        input.addEventListener('change', () => selectBookingType(input.value));
    });

    const productName = document.querySelector('[data-product-summary-name]');
    const productDetail = document.querySelector('[data-product-summary-detail]');

    const selectProduct = (value, shouldScroll = false) => {
        const selectedCard = document.querySelector(`[data-product-card][data-select-product="${value}"]`);

        document.querySelectorAll('[data-product-card]').forEach((card) => {
            card.classList.toggle('is-selected', card.dataset.selectProduct === value);
        });

        document.querySelectorAll('[data-booking-card]').forEach((card) => {
            card.classList.remove('is-selected');
        });

        showPurchaseFlow();

        document.querySelectorAll('input[name="product_id"]').forEach((input) => {
            input.checked = input.value === value;
        });

        if (selectedCard && productName && productDetail) {
            productName.textContent = selectedCard.dataset.productName || '';
            productDetail.textContent = [
                selectedCard.dataset.productDetail,
                selectedCard.dataset.productPrice,
            ].filter(Boolean).join(' · ');
        }

        if (shouldScroll) {
            purchaseForm?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    document.querySelectorAll('[data-product-card]').forEach((card) => {
        card.addEventListener('click', (event) => {
            event.preventDefault();
            selectProduct(card.dataset.selectProduct, true);
        });
    });

    document.querySelectorAll('input[name="product_id"]').forEach((input) => {
        input.addEventListener('change', () => selectProduct(input.value));
    });

    const updateChildrenResponsibility = () => {
        const field = document.getElementById('children-responsibility');

        if (! field) {
            return;
        }

        const checkbox = field.querySelector('input[type="checkbox"]');
        const isVisible = document.querySelector('[name="bringing_children"]:checked')?.value === '1';

        field.classList.toggle('hidden', ! isVisible);
        checkbox.required = isVisible;

        if (! isVisible) {
            checkbox.checked = false;
        }
    };

    document.querySelectorAll('[data-children-toggle]').forEach((input) => {
        input.addEventListener('change', updateChildrenResponsibility);
    });

    updateChildrenResponsibility();
});
