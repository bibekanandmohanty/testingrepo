import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from 'src/environments/environment';
import * as jwt from 'jsonwebtoken';
@Injectable()
export class TokenInterceptor implements HttpInterceptor {
    public token: any;
    public storeId: any;
    /**
     * Generate jwt token.
     * 
     * @returns  token string
     */
    generateJwtToken() {
        const payload = { attr1: 'value 1', attr2: 'testing' };
        const getToken = jwt.sign({ data: payload }, environment.SECRET_KEY, {
            algorithm: 'HS256',
            expiresIn: '24h',   // 2 Hrs, Eg: 60, "2 days", "10h", "7d", "20" (20ms)
            // expiresIn: (2 * 60),   //2 Mins
            audience: environment.STORE_URL,
            issuer: 'guest-' + this.generateRandomString(20)
        });
        return getToken;
    }
    /**
     * Generate random string
     * 
     * @param  length - String length
     * 
     * @returns  Random string
     */
    generateRandomString(length) {
        let result = '';
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    /**
     * Check jwt token expiry.
     * 
     * @returns token expiry status.
     */
    checkJwtTokenExpiry() {
        let status = 0;
        jwt.verify(this.token, environment.SECRET_KEY, (err, decoded) => {
            if (err) {
                status = 1;
            }
        });
        return status;
    }
    intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
        if (!this.token) {
            this.token = this.generateJwtToken();
        } else {
            const isExpired = this.checkJwtTokenExpiry();
            if (isExpired) {
                this.token = this.generateJwtToken();
            }
        }
        localStorage.setItem('token', this.token);
        req = req.clone({
            headers: req.headers.set('token', `Bearer ${this.token}`),
        });

        // add store id to the url params of api call starts
        if(!req.url.includes('.json')) {
            this.storeId = localStorage.getItem('storeId') ? localStorage.getItem('storeId') : '1';
            if (req.method.toLowerCase() === 'post') {
                if (req.body instanceof FormData) {
                    req =  req.clone({
                        body: req.body.set('store_id', this.storeId)
                    })
                } else {
                    const tempParam = {}; tempParam['store_id'] = this.storeId;
                    req =  req.clone({
                        body: {...req.body, ...tempParam}
                    })
                }            
            } else {
                req = req.clone({
                    params: req.params.set('store_id', this.storeId)
                });
            } 
        }
        // add store id to the url params of api call ends

        return next.handle(req);
    }
}
