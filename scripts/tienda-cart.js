// tienda-cart.js - منفصل عن ملفات JavaScript الأخرى لتجنب تعارض المتغيرات
(function() {
    // دالة للحصول على المسار الأساسي للموقع مع مسار Tienda الصحيح
    function getSiteBaseUrl() {
        // نستخدم مسار Tienda الثابت بغض النظر عن موقع المستخدم الحالي
        return window.location.protocol + '//' + window.location.host + '/Tienda/';
    }
    // إضافة المنتج للعربة عند النقر
    document.addEventListener('DOMContentLoaded', function() {
        // الحصول على جميع أزرار إضافة المنتج للعربة
        const addButtons = document.querySelectorAll('.add-to-cart-btn');
        
        // إضافة حدث النقر لكل زر
        addButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // الحصول على بيانات المنتج من السمات
                const productId = this.getAttribute('data-product-id');
                
                // التحقق من وجود معرف المنتج
                if (!productId) {
                    console.error('خطأ: لم يتم تحديد رقم المنتج');
                    return;
                }
                
                // الحصول على معلومات المنتج من السمات أو من العناصر المحيطة
                let productName = this.getAttribute('data-product-name');
                let productPrice = this.getAttribute('data-product-price');
                let productImage = this.getAttribute('data-product-image');
                
                // إذا لم تكن السمات موجودة، حاول الحصول عليها من عناصر HTML المحيطة
                const productCard = this.closest('.product-card');
                if (productCard) {
                    // الحصول على اسم المنتج إذا لم يكن محدداً
                    if (!productName) {
                        const titleEl = productCard.querySelector('.product-title');
                        if (titleEl) productName = titleEl.textContent.trim();
                        else productName = 'Unknown Product';
                    }
                    
                    // الحصول على سعر المنتج إذا لم يكن محدداً
                    if (!productPrice) {
                        const priceEl = productCard.querySelector('.price');
                        if (priceEl) {
                            // إزالة رمز العملة $ والنقاط والفواصل
                            productPrice = priceEl.textContent.replace(/[^0-9.]/g, '');
                        }
                        else productPrice = '0';
                    }
                    
                    // الحصول على صورة المنتج إذا لم تكن محددة
                    if (!productImage) {
                        const imgEl = productCard.querySelector('img');
                        if (imgEl) productImage = imgEl.getAttribute('src');
                        else productImage = 'assets/images/product-placeholder.png';
                    }
                }
                
                // إظهار رسالة نجاح مؤقتة
                showSuccessMessage('تمت إضافة المنتج إلى العربة');
                
                // إرسال البيانات للخادم لإضافة المنتج للعربة
                addProductToCart(productId, 1, productName, productPrice, productImage);
            });
        });
    });
    
    // دالة إظهار رسالة النجاح
    function showSuccessMessage(message) {
        // إنشاء عنصر الرسالة
        const msgElement = document.createElement('div');
        msgElement.className = 'success-message';
        msgElement.textContent = message;
        msgElement.style.position = 'fixed';
        msgElement.style.top = '20px';
        msgElement.style.right = '20px';
        msgElement.style.backgroundColor = '#4CAF50';
        msgElement.style.color = 'white';
        msgElement.style.padding = '15px 20px';
        msgElement.style.borderRadius = '4px';
        msgElement.style.boxShadow = '0 2px 8px rgba(0,0,0,0.2)';
        msgElement.style.zIndex = '9999';
        msgElement.style.direction = 'rtl';
        msgElement.style.fontFamily = 'Arial, sans-serif';
        
        // إضافة الرسالة للصفحة
        document.body.appendChild(msgElement);
        
        // إزالة الرسالة بعد 3 ثواني
        setTimeout(() => {
            msgElement.style.opacity = '0';
            msgElement.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                document.body.removeChild(msgElement);
            }, 500);
        }, 3000);
    }
    
    // دالة إضافة المنتج للعربة
    function addProductToCart(productId, quantity, productName, productPrice, productImage) {
        if (!productId) {
            console.error('خطأ: لم يتم تحديد رقم المنتج');
            return;
        }
        
        console.log('إضافة المنتج برقم:', productId, 'الاسم:', productName, 'السعر:', productPrice);
        
        // استخدام عنوان URL مطلق لمنع مشاكل حل المسار
        const apiPath = window.location.origin + '/Tienda/api/add_to_cart.php';
        console.log('إضافة إلى سلة التسوق في:', apiPath);
        
        // إرسال طلب AJAX لإضافة المنتج للعربة
        fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity,
                product_name: productName,
                product_price: productPrice,
                product_image: productImage
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('فشل الاتصال بالخادم');
            }
            return response.json();
        })
        .then(data => {
            console.log('استجابة API:', data);
            
            if (data.status === 'success') {
                // تحديث عداد العربة في القائمة
                updateCartCounter(data.cart_count || 1);
                
                // Show success message
                alert('تمت إضافة المنتج للعربة بنجاح');
            } else {
                console.error('خطأ في إضافة المنتج للعربة:', data.message);
                alert('خطأ: ' + data.message);
            }
        })
        .catch(error => {
            console.error('خطأ في إضافة المنتج للعربة:', error);
        });
    }
    
    // دالة تحديث عداد العربة
    function updateCartCounter(count) {
        const cartCounters = document.querySelectorAll('.nav-cart-after');
        
        cartCounters.forEach(counter => {
            counter.textContent = count;
            counter.style.display = 'block';
        });
    }
})();
