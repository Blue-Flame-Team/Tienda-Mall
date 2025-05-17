<?php
// صفحة اختبار API إضافة المنتج للسلة
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار API إضافة المنتج للسلة</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        button {
            padding: 10px 15px;
            background-color: #DB4444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        select, input {
            padding: 8px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <h1>اختبار API إضافة المنتج للسلة</h1>
    
    <div>
        <label for="product_id">رقم المنتج:</label>
        <input type="number" id="product_id" value="1" min="1">
        
        <label for="quantity">الكمية:</label>
        <input type="number" id="quantity" value="1" min="1">
        
        <button onclick="testAddToCart()">إضافة للسلة</button>
    </div>
    
    <div class="result" id="result">
        <p>النتيجة ستظهر هنا...</p>
    </div>
    
    <script>
        function testAddToCart() {
            const productId = document.getElementById('product_id').value;
            const quantity = document.getElementById('quantity').value;
            const resultElement = document.getElementById('result');
            
            // إظهار حالة التحميل
            resultElement.innerHTML = '<p>جاري إضافة المنتج للسلة...</p>';
            
            // إرسال الطلب
            fetch('api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: parseInt(productId),
                    quantity: parseInt(quantity)
                })
            })
            .then(response => {
                // عرض النص الأصلي للاستجابة لمعرفة ما يحدث بالضبط
                return response.text().then(text => {
                    let html = '<h3>استجابة الخادم الأصلية:</h3>';
                    html += '<pre style="background:#f5f5f5;padding:10px;overflow:auto">' + text + '</pre>';
                    
                    // محاولة تحويل النص إلى JSON إذا كان ممكناً
                    try {
                        const json = JSON.parse(text);
                        html += '<h3>بيانات JSON:</h3>';
                        html += '<pre style="background:#f5f5f5;padding:10px;overflow:auto">' + JSON.stringify(json, null, 2) + '</pre>';
                    } catch(e) {
                        html += '<h3>خطأ في تحليل JSON:</h3>';
                        html += '<p style="color:red">' + e.message + '</p>';
                    }
                    
                    resultElement.innerHTML = html;
                });
            })
            .catch(error => {
                resultElement.innerHTML = '<p style="color:red">خطأ: ' + error.message + '</p>';
            });
        }
    </script>
</body>
</html>
