</div>
<!-- /.content-wrapper -->

<footer class="main-footer">
    <div class="float-right d-none d-sm-block">
        <b>الإصدار</b> 1.0.0
    </div>
    <strong>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?> <a href="../index.php">متجر Tienda</a>.</strong>
</footer>

</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js" crossorigin="anonymous"></script>

<!-- Popper.js (required for Bootstrap tooltips and popovers) -->
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" crossorigin="anonymous"></script>

<!-- Bootstrap 4 (separate files instead of bundle) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>

<!-- AdminLTE App -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/admin-lte/3.1.0/js/adminlte.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- Fix RTL issues with AdminLTE -->
<script>
    // Fix RTL dropdown issues
    $(document).ready(function() {
        // Fix dropdown menu positions
        $('.dropdown-menu-right').removeClass('dropdown-menu-right').addClass('dropdown-menu-left');
        
        // Fix sidebar menu arrows
        $('.nav-sidebar .has-treeview > a > .right').removeClass('fa-angle-left').addClass('fa-angle-right');
        
        // Fix sidebar collapse behavior for RTL
        $('[data-widget="pushmenu"]').on('click', function() {
            setTimeout(function() {
                // Adjust content margin
                if ($('body').hasClass('sidebar-collapse')) {
                    $('.content-wrapper').css('margin-right', '4.6rem');
                } else {
                    $('.content-wrapper').css('margin-right', '250px');
                }
            }, 300);
        });
    });
</script>

<!-- Custom JS -->
<script>
    $(document).ready(function() {
        // Initialize tooltips
        if (typeof $.fn.tooltip !== 'undefined') {
            $('[data-toggle="tooltip"]').tooltip();
        }
        
        // Initialize popovers
        if (typeof $.fn.popover !== 'undefined') {
            $('[data-toggle="popover"]').popover();
        }
        
        // Initialize datatables with enhanced features
        if ($.fn.DataTable && $('.datatable').length > 0) {
            $('.datatable').DataTable({
                "responsive": true,
                "autoWidth": false,
                "dom": '<"row mb-3"<"col-md-6"B><"col-md-6"f>>' +
                        '<"row"<"col-12"tr>>' +
                        '<"row mt-3"<"col-md-5"i><"col-md-7"p>>',
                "buttons": [
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-sm btn-outline-secondary',
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-sm btn-outline-secondary',
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> طباعة',
                        className: 'btn btn-sm btn-outline-secondary',
                        exportOptions: {
                            columns: ':visible:not(:last-child)'
                        }
                    },
                    {
                        extend: 'colvis',
                        text: '<i class="fas fa-columns"></i> الأعمدة',
                        className: 'btn btn-sm btn-outline-secondary'
                    }
                ],
                "language": {
                    "lengthMenu": "عرض _MENU_ سجلات لكل صفحة",
                    "zeroRecords": "لا يوجد بيانات مطابقة",
                    "info": "عرض _START_ إلى _END_ من _TOTAL_ سجل",
                    "infoEmpty": "لا يوجد بيانات للعرض",
                    "infoFiltered": "(تم البحث في _MAX_ سجلات)",
                    "search": "بحث:",
                    "paginate": {
                        "first": "الأول",
                        "last": "الأخير",
                        "next": "التالي",
                        "previous": "السابق"
                    },
                    "buttons": {
                        "copy": "نسخ",
                        "excel": "تصدير Excel",
                        "csv": "تصدير CSV",
                        "pdf": "تصدير PDF",
                        "print": "طباعة",
                        "colvis": "إظهار/إخفاء الأعمدة"
                    }
                }
            });
        }
        
        // Product search functionality
        $('#searchInput').on('keyup', function() {
            let value = $(this).val().toLowerCase();
            $('.datatable tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Card animations
        $('.card').each(function(index) {
            $(this).css('animation-delay', (index * 0.1) + 's');
        });
        
        // Fix for RTL specific issues
        if ($('html').attr('dir') === 'rtl') {
            // Fix dropdown menus
            $('.dropdown-menu').addClass('dropdown-menu-right');
            
            // Fix datatable buttons alignment
            $('.dt-buttons').addClass('text-right');
        }
    });
    
    // File input preview
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').addClass("selected").html(fileName);
        
        // Image preview for product images
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result).show();
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
</script>
</body>
</html>
