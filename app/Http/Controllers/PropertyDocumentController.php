<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PropertyDocumentController extends Controller
{
    /**
     * View property document
     * Handles viewing of agreement documents (identity_proof, national_id_passport, utilities_bills, power_of_attorney)
     */
    public function viewDocument(Request $request, $propertyId, $documentType)
    {
        try {
            // Verify property exists
            $property = Property::find($propertyId);
            if (!$property) {
                Log::warning('Property document view: Property not found', ['property_id' => $propertyId]);
                abort(404, 'Property not found');
            }

            // Map document types to database fields
            $documentFields = [
                'identity_proof' => 'identity_proof',
                'national-id' => 'national_id_passport',
                'utilities-bills' => 'utilities_bills',
                'power-of-attorney' => 'power_of_attorney',
            ];

            if (!isset($documentFields[$documentType])) {
                Log::warning('Property document view: Invalid document type', [
                    'property_id' => $propertyId,
                    'document_type' => $documentType,
                ]);
                abort(404, 'Invalid document type');
            }

            $fieldName = $documentFields[$documentType];
            $fileName = $property->getRawOriginal($fieldName);

            if (empty($fileName)) {
                Log::warning('Property document view: Document field is empty', [
                    'property_id' => $propertyId,
                    'document_type' => $documentType,
                    'field_name' => $fieldName,
                ]);
                abort(404, 'Document not found');
            }

        // Clean filename - remove trailing dots
        $fileName = rtrim($fileName, '.');

        // Get file path
        $configPaths = [
            'identity_proof' => config('global.PROPERTY_IDENTITY_PROOF_PATH'),
            'national_id_passport' => config('global.PROPERTY_NATIONAL_ID_PATH'),
            'utilities_bills' => config('global.PROPERTY_UTILITIES_PATH'),
            'power_of_attorney' => config('global.PROPERTY_POA_PATH'),
        ];

        // Construct path properly - config paths start with /
        $configPath = trim($configPaths[$fieldName], '/');
        $basePath = public_path('images/' . $configPath);
        
        // Check if download is requested
        $download = $request->has('download') && $request->get('download') == '1';
        
        // MIME type mapping
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        
        Log::info('Property document view attempt', [
            'property_id' => $propertyId,
            'document_type' => $documentType,
            'field_name' => $fieldName,
            'file_name' => $fileName,
            'base_path' => $basePath,
        ]);
        
        // Strategy 1: Check if filename already has extension
        $fullPath = $basePath . '/' . $fileName;
        if (File::exists($fullPath)) {
            try {
                $mimeType = File::mimeType($fullPath);
            } catch (\Exception $e) {
                $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
            }
            $disposition = $download ? 'attachment' : 'inline';
            $headers = [
                'Content-Type' => $mimeType,
                'Content-Disposition' => $disposition . '; filename="' . basename($fileName) . '"',
            ];
            
            // Add headers for browser preview (especially for PDFs and images)
            if (!$download) {
                $headers['X-Content-Type-Options'] = 'nosniff';
                // For PDFs, ensure browser can preview
                if ($mimeType === 'application/pdf') {
                    $headers['Content-Disposition'] = 'inline; filename="' . basename($fileName) . '"';
                }
            }
            
            return response()->file($fullPath, $headers);
        }

        // Strategy 2: Try common extensions if file doesn't exist
        $commonExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        foreach ($commonExtensions as $ext) {
            $testPath = $basePath . '/' . $fileName . '.' . $ext;
            if (File::exists($testPath)) {
                $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
                $disposition = $download ? 'attachment' : 'inline';
                $headers = [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => $disposition . '; filename="' . basename($fileName) . '.' . $ext . '"',
                ];
                
                if (!$download) {
                    $headers['X-Content-Type-Options'] = 'nosniff';
                }
                
                return response()->file($testPath, $headers);
            }
        }
        
        // Strategy 3: Search for files starting with the filename
        if (is_dir($basePath)) {
            $files = glob($basePath . '/' . $fileName . '.*');
            if (!empty($files)) {
                $foundFile = $files[0];
                $extension = strtolower(pathinfo($foundFile, PATHINFO_EXTENSION));
                $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
                $disposition = $download ? 'attachment' : 'inline';
                $headers = [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => $disposition . '; filename="' . basename($foundFile) . '"',
                ];
                
                if (!$download) {
                    $headers['X-Content-Type-Options'] = 'nosniff';
                }
                
                return response()->file($foundFile, $headers);
            }
        }

        // If using S3 or files not found locally, try S3
        $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));
        
        // Always try S3 if local file not found (files might be on S3)
        // But only if S3 is properly configured
        $s3Configured = false;
        try {
            $s3Config = config('filesystems.disks.s3');
            if (!empty($s3Config) && isset($s3Config['driver']) && $s3Config['driver'] === 's3') {
                $s3Configured = !empty(env('AWS_ACCESS_KEY_ID')) && 
                               !empty(env('AWS_SECRET_ACCESS_KEY')) && 
                               !empty(env('AWS_BUCKET'));
            }
        } catch (\Exception $e) {
            Log::debug('S3 configuration check failed', ['error' => $e->getMessage()]);
        }
        
        if ($s3Configured) {
            try {
                // S3 path should be: images/property_identity_proof/filename.jpg
                $s3BasePath = 'images/' . $configPath . '/';
                
                // Try exact filename first
                $s3Path = $s3BasePath . $fileName;
                try {
                    if (Storage::disk('s3')->exists($s3Path)) {
                        $fileContent = Storage::disk('s3')->get($s3Path);
                        try {
                            $mimeType = Storage::disk('s3')->mimeType($s3Path);
                        } catch (\Exception $e) {
                            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
                        }
                        $disposition = $download ? 'attachment' : 'inline';
                        $headers = [
                            'Content-Type' => $mimeType,
                            'Content-Disposition' => $disposition . '; filename="' . basename($fileName) . '"',
                        ];
                        
                        if (!$download) {
                            $headers['X-Content-Type-Options'] = 'nosniff';
                            $headers['Cache-Control'] = 'public, max-age=3600';
                        }
                        
                        return response($fileContent, 200, $headers);
                    }
                } catch (\Exception $e) {
                    Log::debug('S3 exists check failed', ['path' => $s3Path, 'error' => $e->getMessage()]);
                }

                // Try with common extensions on S3
                foreach ($commonExtensions as $ext) {
                    $s3TestPath = $s3BasePath . $fileName . '.' . $ext;
                    try {
                        if (Storage::disk('s3')->exists($s3TestPath)) {
                            $fileContent = Storage::disk('s3')->get($s3TestPath);
                            $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
                            $disposition = $download ? 'attachment' : 'inline';
                            $headers = [
                                'Content-Type' => $mimeType,
                                'Content-Disposition' => $disposition . '; filename="' . basename($fileName) . '.' . $ext . '"',
                            ];
                            
                            if (!$download) {
                                $headers['X-Content-Type-Options'] = 'nosniff';
                                $headers['Cache-Control'] = 'public, max-age=3600';
                            }
                            
                            return response($fileContent, 200, $headers);
                        }
                    } catch (\Exception $e) {
                        // Continue to next extension
                        continue;
                    }
                }
                
                // Try to list files in S3 directory and find matching file
                try {
                    $s3Files = Storage::disk('s3')->files($s3BasePath);
                    foreach ($s3Files as $s3File) {
                        $s3FileName = basename($s3File);
                        // Check if filename starts with our filename
                        if (strpos($s3FileName, $fileName) === 0) {
                            $fileContent = Storage::disk('s3')->get($s3File);
                            $extension = strtolower(pathinfo($s3FileName, PATHINFO_EXTENSION));
                            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
                            $disposition = $download ? 'attachment' : 'inline';
                            $headers = [
                                'Content-Type' => $mimeType,
                                'Content-Disposition' => $disposition . '; filename="' . $s3FileName . '"',
                            ];
                            
                            if (!$download) {
                                $headers['X-Content-Type-Options'] = 'nosniff';
                                $headers['Cache-Control'] = 'public, max-age=3600';
                            }
                            
                            return response($fileContent, 200, $headers);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not list S3 files', ['path' => $s3BasePath, 'error' => $e->getMessage()]);
                }
            } catch (\Exception $e) {
                Log::error('Error fetching document from S3: ' . $e->getMessage(), [
                    'property_id' => $propertyId,
                    'document_type' => $documentType,
                    'file_name' => $fileName,
                    'error_type' => get_class($e),
                    'trace' => substr($e->getTraceAsString(), 0, 500), // Limit trace length
                ]);
                // Don't abort here, continue to show helpful error message
            }
        } else {
            Log::info('S3 not configured, skipping S3 file lookup', [
                'property_id' => $propertyId,
                'disk' => $disk,
            ]);
        }

        // File not found - return helpful error with more details
        Log::warning('Property document not found', [
            'property_id' => $propertyId,
            'document_type' => $documentType,
            'field_name' => $fieldName,
            'file_name' => $fileName,
            'base_path' => $basePath,
            'directory_exists' => is_dir($basePath),
            'disk' => $disk,
        ]);

        // Return a more helpful error message
        $errorMessage = "Document file not found.\n";
        $errorMessage .= "Property ID: {$propertyId}\n";
        $errorMessage .= "Document Type: {$documentType}\n";
        $errorMessage .= "File Name: {$fileName}\n";
        $errorMessage .= "Expected Path: {$basePath}\n";
        $errorMessage .= "Directory Exists: " . (is_dir($basePath) ? 'Yes' : 'No') . "\n";
        $errorMessage .= "Storage Disk: {$disk}\n";
        $errorMessage .= "\nPlease check:\n";
        $errorMessage .= "1. If files are stored on S3, verify S3 configuration\n";
        $errorMessage .= "2. If files are stored locally, check the directory: {$basePath}\n";
        $errorMessage .= "3. Verify the filename in database matches the actual file name";
        
        abort(404, $errorMessage);
        } catch (\Exception $e) {
            Log::error('Property document view error', [
                'property_id' => $propertyId,
                'document_type' => $documentType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500, 'Error loading document: ' . $e->getMessage());
        }
    }
}

