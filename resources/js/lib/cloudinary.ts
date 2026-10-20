export interface CloudinaryUpload {
    secure_url: string;
    width?: number;
    height?: number;
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/** Upload through the signed preset: the server signs, the browser sends the file straight to Cloudinary. */
export async function uploadImage(file: File): Promise<CloudinaryUpload> {
    const signRes = await fetch('/admin/uploads/sign', {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrfToken() },
        credentials: 'same-origin',
    });

    if (!signRes.ok) {
        throw new Error('Could not sign upload');
    }

    const sig = await signRes.json();
    const formData = new FormData();
    formData.append('file', file);
    formData.append('api_key', sig.api_key);
    formData.append('timestamp', String(sig.timestamp));
    formData.append('signature', sig.signature);
    formData.append('upload_preset', sig.upload_preset);
    formData.append('folder', sig.folder);

    const res = await fetch(
        `https://api.cloudinary.com/v1_1/${sig.cloud_name}/image/upload`,
        { method: 'POST', body: formData },
    );

    if (!res.ok) {
        throw new Error('Upload failed');
    }

    return res.json();
}
