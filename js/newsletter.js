/**
 * Newsletter subscription handler
 * Handles form submission and displays success message
 */
(function () {
    'use strict';

    // Handle all newsletter forms on the page
    document.addEventListener('DOMContentLoaded', function () {
        const newsletterForms = document.querySelectorAll('.newsletter-form');

        newsletterForms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const emailInput = form.querySelector('input[type="email"]');
                const submitButton = form.querySelector('button[type="submit"]');
                const email = emailInput.value.trim();

                // Basic validation
                if (!email || !isValidEmail(email)) {
                    showMessage(form, 'Molimo unesite ispravan email.', 'error');
                    return;
                }

                // Disable button during submission
                submitButton.disabled = true;
                submitButton.textContent = 'Šaljem...';

                // Send data to server
                const formData = new FormData();
                formData.append('email', email);

                fetch('process_news.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data.status === 'success') {
                            // Replace form with success message
                            showSuccessMessage(form);
                        } else {
                            showMessage(form, data.message || 'Došlo je do greške.', 'error');
                            submitButton.disabled = false;
                            submitButton.textContent = 'Prijava';
                        }
                    })
                    .catch(function (error) {
                        console.error('Error:', error);
                        showMessage(form, 'Došlo je do greške. Molimo pokušajte ponovo.', 'error');
                        submitButton.disabled = false;
                        submitButton.textContent = 'Prijava';
                    });
            });
        });
    });

    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function showMessage(form, message, type) {
        // Remove any existing messages
        const existingMsg = form.querySelector('.newsletter-message');
        if (existingMsg) {
            existingMsg.remove();
        }

        // Create message element
        const msgDiv = document.createElement('div');
        msgDiv.className = 'newsletter-message newsletter-message-' + type;
        msgDiv.textContent = message;
        msgDiv.style.marginTop = '10px';
        msgDiv.style.padding = '10px';
        msgDiv.style.borderRadius = '5px';
        msgDiv.style.textAlign = 'center';

        if (type === 'error') {
            msgDiv.style.backgroundColor = '#f8d7da';
            msgDiv.style.color = '#721c24';
            msgDiv.style.border = '1px solid #f5c6cb';
        } else {
            msgDiv.style.backgroundColor = '#d4edda';
            msgDiv.style.color = '#155724';
            msgDiv.style.border = '1px solid #c3e6cb';
        }

        form.appendChild(msgDiv);

        // Auto-remove after 5 seconds
        setTimeout(function () {
            msgDiv.remove();
        }, 5000);
    }

    function showSuccessMessage(form) {
        // Get the parent container
        const container = form.closest('.footer-newsletter, .col-xl-6');

        if (!container) {
            // Fallback: just show message in form
            form.innerHTML = '<div class="newsletter-success" style="padding: 20px; text-align: center;">' +
                '<h3 style="color: #155724; margin-bottom: 10px;">✓ Hvala na prijavi!</h3>' +
                '<p style="color: #155724;">Uspešno ste se prijavili za naš newsletter.</p>' +
                '</div>';
            return;
        }

        // Replace entire container content with success message
        container.innerHTML = '<div class="newsletter-success" style="padding: 30px; text-align: center;">' +
            '<div style="font-size: 48px; color: #28a745; margin-bottom: 15px;">✓</div>' +
            '<h3 style="color: #155724; margin-bottom: 10px; font-size: 24px;">Hvala na prijavi!</h3>' +
            '<p style="color: #155724; font-size: 16px;">Uspešno ste se prijavili za naš newsletter.<br>Uskoro ćete dobijati najnovije vesti i informacije.</p>' +
            '</div>';
    }
})();
