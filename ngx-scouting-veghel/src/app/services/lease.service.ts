import { Injectable } from "@angular/core";
import { Observable, of } from 'rxjs';
import { SvLeasedBase } from '@interfaces';

@Injectable({ providedIn: 'root' })
export class SvLeaseService {
  getForMonth$(month: number): Observable<SvLeasedBase[]> {
    return of([]);
  }
}
