import { fromEvent, Observable } from 'rxjs';
import { pluck } from 'rxjs/operators';

export function convertImageToBase64(fileReader: FileReader, fileToRead: File): Observable<string> {
    fileReader.readAsDataURL(fileToRead);
    return fromEvent(fileReader, 'load').pipe(pluck('currentTarget', 'result'));
}
