<!DOCTYPE html>
<html>
<head>
    <title>Test Upload Logo</title>
</head>
<body>
    <h2>Test Company Logo Upload</h2>
    
    <form action="/dashboard/companies/1" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="PUT">
        
        <div>
            <label>Company Name:</label>
            <input type="text" name="name" value="Test Company" required>
        </div>
        
        <div>
            <label>Logo:</label>
            <input type="file" name="logo" accept="image/*">
        </div>
        
        <div>
            <label>Description:</label>
            <textarea name="description">Test description</textarea>
        </div>
        
        <button type="submit">Update Company</button>
    </form>
</body>
</html>