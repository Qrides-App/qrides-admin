<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Cloud\Firestore\FirestoreClient;

class CopyFirestore extends Command
{
    protected $signature = 'firestore:copy';
    protected $description = 'Copy Firestore data from one project to another';

    public function handle()
    {
        $this->info('Starting Firestore copy...');

        // Source Firestore
        $sourceDb = new FirestoreClient([
            'projectId' => 'rideon-1a627',
            'keyFilePath' => storage_path('firebase/firebase_credentials.json'),
        ]);

        // Target Firestore
        $targetDb = new FirestoreClient([
            'projectId' => 'dummy-b0665',
            'keyFilePath' => storage_path('firebase/firebase_credentials-live.json'),
        ]);

        // Get all collections from source
        $collections = $sourceDb->collections();

        foreach ($collections as $collection) {
            $this->info("Copying collection: " . $collection->id());
            $docs = $collection->documents();

            foreach ($docs as $doc) {
                $targetDb->collection($collection->id())->document($doc->id())->set($doc->data());
                $this->info("Copied document: " . $doc->id());
            }
        }

        $this->info('Firestore copy completed!');
    }
}
