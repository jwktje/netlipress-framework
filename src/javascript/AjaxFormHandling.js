document.addEventListener("DOMContentLoaded", () => {
    let forms = document.querySelectorAll('form[data-netlipress]');
    if (forms) {
        forms.forEach(form => {

            form.addEventListener('submit', e => {
                e.preventDefault();

                let formData = new FormData(form);
                formData.append('action', form.dataset.netlipress);
                form.classList.add('loading');
                form.classList.remove('error');

                function showError(error) {
                    console.error(error);
                    form.classList.remove('loading');
                    form.classList.add('error');
                    let innerError = form.querySelector('.error-message .error');
                    if (innerError) {
                        innerError.innerHTML = error;
                    }
                }

                fetch('/handle-form', {
                    method: 'POST',
                    body: formData
                }).then(res => {
                    if (res.status === 200) {
                        res.json().then(json => {
                            if (json.success) {
                                form.classList.remove('loading');
                                form.classList.add('success');
                                form.reset();
                                setTimeout(() => {
                                    form.classList.remove('success');
                                }, 10000);
                            } else {
                                showError(json.error);
                            }
                        });
                    } else {
                        showError('Error: ' + res.status);
                    }
                });
            });
        })
    }
})
