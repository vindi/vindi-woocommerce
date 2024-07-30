jQuery(document).ready(function ($) {
    const copyLinkPostEditButtons = document.querySelectorAll('.btnCopyPostLink');
    copyLinkPostEditButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const link = e.currentTarget.href;
            const tempInput = document.createElement('input');
            tempInput.value = link;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
        });
    });
})