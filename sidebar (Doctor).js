// sidebar.js
document.addEventListener('DOMContentLoaded', function () {
    // Add click event listeners to all dropdowns
    document.querySelectorAll('.dropdown > a').forEach(dropdownLink => {
        dropdownLink.addEventListener('click', function (event) {
            event.preventDefault(); // Prevent default link behavior

            // Toggle the 'active' class on the parent <li> element
            const parentLi = this.parentElement;
            parentLi.classList.toggle('active');

            // Close other open submenus (optional behavior)
            document.querySelectorAll('.dropdown').forEach(li => {
                if (li !== parentLi) {
                    li.classList.remove('active');
                }
            });
        });
    });
});
